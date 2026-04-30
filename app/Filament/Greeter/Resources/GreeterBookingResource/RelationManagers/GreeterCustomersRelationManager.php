<?php

namespace App\Filament\Greeter\Resources\GreeterBookingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use App\Models\BookingCustomer;

class GreeterCustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    protected static ?string $title = 'Passenger Attendance';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-users';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->heading('Passenger Attendance')
            ->description('Mark each passenger individually. Actions save instantly.')
            ->paginated(false)
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex()
                    ->color('gray'),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->weight('semibold')
                    ->description(fn (BookingCustomer $record): ?string => $record->is_primary ? 'Lead Passenger' : null)
                    ->color(fn (BookingCustomer $record): ?string => $record->is_primary ? 'primary' : null),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'adult' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('—'),

                TextColumn::make('nationality')
                    ->label('Nationality')
                    ->placeholder('—'),

                TextColumn::make('passport_number')
                    ->label('Passport')
                    ->placeholder('—'),

                TextColumn::make('weight_kg')
                    ->label('Weight')
                    ->suffix(' kg')
                    ->placeholder('—'),

                TextColumn::make('attendance')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'show'    => 'success',
                        'no_show' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'show'    => '✅ Showed',
                        'no_show' => '❌ No-Show',
                        default   => '⏳ Pending',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('mark_all_show')
                    ->label('Mark All Show')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function () {
                        $this->getOwnerRecord()->customers()->update(['attendance' => 'show']);
                        $this->syncBookingAttendance();
                        Notification::make()->title('✅ All passengers marked as Show')->success()->send();
                    }),

                Action::make('mark_all_no_show')
                    ->label('Mark All No-Show')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function () {
                        $this->getOwnerRecord()->customers()->update(['attendance' => 'no_show']);
                        $this->syncBookingAttendance();
                        Notification::make()->title('❌ All passengers marked as No-Show')->danger()->send();
                    }),

                // ── Override: set gate count without per-row records ──────────
                Action::make('set_showed_count')
                    ->label(function (): string {
                        $booking = $this->getOwnerRecord();
                        if ($booking->attended_pax !== null) {
                            return "🎯 Override: {$booking->attended_pax}/{$booking->getTotalPax()} PAX";
                        }
                        return '🎯 Set Showed Count';
                    })
                    ->color(fn (): string => $this->getOwnerRecord()->attended_pax !== null ? 'warning' : 'gray')
                    ->icon('heroicon-o-calculator')
                    ->modalHeading('Override PAX Attendance Count')
                    ->modalDescription('Use this when passenger records are incomplete. Enter the actual number of passengers that showed up at the gate. This overrides individual row attendance marks.')
                    ->modalWidth('sm')
                    ->form(function (): array {
                        $booking  = $this->getOwnerRecord();
                        $totalPax = $booking->getTotalPax();
                        return [
                            Forms\Components\TextInput::make('attended_pax')
                                ->label('Passengers who showed up')
                                ->helperText("Enter a number between 0 and {$totalPax} (total booking PAX)")
                                ->numeric()
                                ->minValue(0)
                                ->maxValue($totalPax)
                                ->default($booking->attended_pax)
                                ->required(),
                        ];
                    })
                    ->action(function (array $data): void {
                        $booking = $this->getOwnerRecord();
                        $booking->update(['attended_pax' => (int) $data['attended_pax']]);
                        $this->syncBookingAttendance();
                        Notification::make()
                            ->title("🎯 Attendance set to {$data['attended_pax']}/{$booking->getTotalPax()} PAX")
                            ->success()
                            ->send();
                    }),

                // ── Clear the override ────────────────────────────────────────
                Action::make('clear_override')
                    ->label('Clear Override')
                    ->color('gray')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => $this->getOwnerRecord()->attended_pax !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Clear PAX Override')
                    ->modalDescription('This will remove the manual count override. The attendance will be calculated from the individual passenger rows again.')
                    ->action(function (): void {
                        $booking = $this->getOwnerRecord();
                        $booking->update(['attended_pax' => null]);
                        $this->syncBookingAttendance();
                        Notification::make()->title('Override cleared — using row data')->success()->send();
                    }),
            ])
            ->actions([
                Action::make('mark_show')
                    ->label('Show')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->hidden(fn (BookingCustomer $record): bool => $record->attendance === 'show')
                    ->action(function (BookingCustomer $record) {
                        $record->update(['attendance' => 'show']);
                        $this->syncBookingAttendance();
                        Notification::make()->title("✅ Show — {$record->full_name}")->success()->send();
                    }),

                Action::make('mark_no_show')
                    ->label('No-Show')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->button()
                    ->hidden(fn (BookingCustomer $record): bool => $record->attendance === 'no_show')
                    ->action(function (BookingCustomer $record) {
                        $record->update(['attendance' => 'no_show']);
                        $this->syncBookingAttendance();
                        Notification::make()->title("❌ No-Show — {$record->full_name}")->danger()->send();
                    }),

                Action::make('reset')
                    ->label('Reset')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->hidden(fn (BookingCustomer $record): bool => $record->attendance === 'pending')
                    ->action(function (BookingCustomer $record) {
                        $record->update(['attendance' => 'pending']);
                        $this->syncBookingAttendance();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_selected_show')
                        ->label('Mark Selected Show')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['attendance' => 'show']);
                            $this->syncBookingAttendance();
                            Notification::make()->title('✅ Selected passengers marked as Show')->success()->send();
                        }),

                    BulkAction::make('mark_selected_no_show')
                        ->label('Mark Selected No-Show')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['attendance' => 'no_show']);
                            $this->syncBookingAttendance();
                            Notification::make()->title('❌ Selected passengers marked as No-Show')->danger()->send();
                        }),
                ]),
            ]);
    }

    /**
     * Sync the parent booking's attendance summary.
     *
     * Uses attended_pax override if set; otherwise falls back to customer rows.
     * Denominator is always booking.getTotalPax() (adult_pax + child_pax).
     */
    private function syncBookingAttendance(): void
    {
        $booking  = $this->getOwnerRecord();
        $totalPax = $booking->getTotalPax();

        // Use override if set, otherwise count per-row 'show' records
        $showedPax = $booking->attended_pax
            ?? $booking->customers()->where('attendance', 'show')->count();

        $bookingAttendance = match (true) {
            $totalPax === 0                  => 'pending',
            $showedPax === $totalPax         => 'show',
            $showedPax === 0                 => 'pending',
            default                          => 'pending', // partial — still pending until all marked
        };

        $booking->update(['attendance' => $bookingAttendance]);
    }
}
