<?php

namespace App\Filament\Admin\Resources\Greeter;

use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Resources\Greeter\Pages\ListGreeterBookings;
use App\Filament\Admin\Resources\Greeter\Pages\ViewGreeterBooking;

class GreeterBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $slug = 'greeter/bookings';

    protected static ?string $recordTitleAttribute = 'booking_ref';

    public static function getNavigationLabel(): string
    {
        return "Today's Bookings";
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-identification';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Greeter';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'greeter']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
            ->withoutGlobalScopes();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('flight_date', 'asc')
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->badge()
                    ->copyable()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'partner' ? 'purple' : 'info')
                    ->formatStateUsing(fn (string $state): string => $state === 'partner' ? '🤝 Partner' : '✈️ Regular'),

                TextColumn::make('flight_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('flight_time')
                    ->label('Time')
                    ->time('H:i')
                    ->placeholder('—'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(25),

                TextColumn::make('adult_pax')
                    ->label('Adults')
                    ->alignCenter(),

                TextColumn::make('child_pax')
                    ->label('Children')
                    ->alignCenter(),

                TextColumn::make('partner.company_name')
                    ->label('Partner')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('booking_status')
                    ->label('Booking Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default     => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('attendance')
                    ->label('Attendance')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'show'    => 'success',
                        'no_show' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'show'    => '✅ Show',
                        'no_show' => '❌ No-Show',
                        default   => '⏳ Pending',
                    }),
            ])
            ->filters([
                SelectFilter::make('flight_date_range')
                    ->label('Period')
                    ->options([
                        'today' => 'Today Only',
                        'week'  => 'Next 7 Days',
                        'all'   => 'All Upcoming',
                    ])
                    ->default('today')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'today') {
                            'week'  => $query->whereBetween('flight_date', [today(), today()->addDays(7)]),
                            'all'   => $query->whereDate('flight_date', '>=', today()),
                            default => $query->whereDate('flight_date', today()),
                        };
                    }),

                SelectFilter::make('attendance')
                    ->label('Attendance')
                    ->options([
                        'pending' => '⏳ Pending',
                        'show'    => '✅ Show',
                        'no_show' => '❌ No-Show',
                    ])
                    ->native(false),

                SelectFilter::make('booking_status')
                    ->label('Booking Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                    ])
                    ->native(false),
            ])
            ->actions([
                Action::make('mark_show')
                    ->label('Mark Show')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Show')
                    ->modalDescription('Confirm this customer group showed up for their flight?')
                    ->visible(fn (Booking $record): bool => $record->attendance !== 'show')
                    ->action(function (Booking $record): void {
                        $record->update(['attendance' => 'show']);
                        Notification::make()
                            ->title('✅ Marked as Show')
                            ->body("Booking {$record->booking_ref} attendance updated.")
                            ->success()
                            ->send();
                    }),

                Action::make('mark_no_show')
                    ->label('No-Show')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as No-Show')
                    ->modalDescription('Mark this booking as a no-show?')
                    ->visible(fn (Booking $record): bool => $record->attendance !== 'no_show')
                    ->action(function (Booking $record): void {
                        $record->update(['attendance' => 'no_show']);
                        Notification::make()
                            ->title('❌ Marked as No-Show')
                            ->body("Booking {$record->booking_ref} marked as no-show.")
                            ->danger()
                            ->send();
                    }),

                ViewAction::make()
                    ->url(fn (Booking $record): string => ViewGreeterBooking::getUrl(['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGreeterBookings::route('/'),
            'view'  => ViewGreeterBooking::route('/{record}'),
        ];
    }
}
