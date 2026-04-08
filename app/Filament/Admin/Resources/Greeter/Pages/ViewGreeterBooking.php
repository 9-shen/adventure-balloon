<?php

namespace App\Filament\Admin\Resources\Greeter\Pages;

use App\Filament\Admin\Resources\Greeter\GreeterBookingResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewGreeterBooking extends ViewRecord
{
    protected static string $resource = GreeterBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_show')
                ->label('✅ Mark Show')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Mark as Show')
                ->modalDescription('Confirm this customer group showed up for their flight?')
                ->visible(fn (): bool => $this->record->attendance !== 'show')
                ->action(function (): void {
                    $this->record->update(['attendance' => 'show']);
                    Notification::make()
                        ->title('✅ Marked as Show')
                        ->success()
                        ->send();
                }),

            Action::make('mark_no_show')
                ->label('❌ No-Show')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Mark as No-Show')
                ->modalDescription('Mark this booking as a no-show?')
                ->visible(fn (): bool => $this->record->attendance !== 'no_show')
                ->action(function (): void {
                    $this->record->update(['attendance' => 'no_show']);
                    Notification::make()
                        ->title('❌ Marked as No-Show')
                        ->danger()
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->components([
                Section::make('Booking Details')
                    ->columns(3)
                    ->components([
                        TextEntry::make('booking_ref')
                            ->label('Booking Ref')
                            ->badge()
                            ->color('primary')
                            ->copyable(),

                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'partner' ? 'purple' : 'info')
                            ->formatStateUsing(fn (string $state): string => $state === 'partner' ? '🤝 Partner' : '✈️ Regular'),

                        TextEntry::make('booking_status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                'completed' => 'info',
                                default     => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        TextEntry::make('product.name')
                            ->label('Product'),

                        TextEntry::make('flight_date')
                            ->label('Flight Date')
                            ->date('d/m/Y'),

                        TextEntry::make('flight_time')
                            ->label('Flight Time')
                            ->time('H:i')
                            ->placeholder('—'),

                        TextEntry::make('adult_pax')
                            ->label('Adults'),

                        TextEntry::make('child_pax')
                            ->label('Children'),

                        TextEntry::make('attendance')
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
                    ]),

                Section::make('Passengers')
                    ->components([
                        RepeatableEntry::make('customers')
                            ->label('')
                            ->schema([
                                TextEntry::make('full_name')
                                    ->label('Name')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                                TextEntry::make('type')
                                    ->label('Type')
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'child' ? 'warning' : 'info')
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                                TextEntry::make('phone')
                                    ->label('Phone')
                                    ->placeholder('—'),

                                TextEntry::make('nationality')
                                    ->label('Nationality')
                                    ->placeholder('—'),

                                TextEntry::make('weight_kg')
                                    ->label('Weight')
                                    ->suffix(' kg')
                                    ->placeholder('—'),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
