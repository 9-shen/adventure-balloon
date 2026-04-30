<?php

namespace App\Filament\Greeter\Resources\GreeterBookingResource\Pages;

use App\Filament\Greeter\Resources\GreeterBookingResource;
use App\Models\Booking;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewGreeterBooking extends ViewRecord
{
    protected static string $resource = GreeterBookingResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->components([
                // ── Booking Summary ───────────────────────────────────────────
                Section::make('Booking Summary')
                    ->columns(4)
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

                        TextEntry::make('pax_label')
                            ->label('PAX Attendance')
                            ->getStateUsing(fn ($record) => $record->getPaxAttendanceLabel())
                            ->badge()
                            ->color(fn ($record) => match (true) {
                                $record->getShowedPax() === $record->getTotalPax() && $record->getTotalPax() > 0 => 'success',
                                $record->getShowedPax() > 0                                                      => 'warning',
                                default                                                                          => 'gray',
                            }),

                        TextEntry::make('product.name')
                            ->label('Product'),

                        TextEntry::make('flight_date')
                            ->label('Flight Date')
                            ->date('d/m/Y'),

                        TextEntry::make('flight_time')
                            ->label('Flight Time')
                            ->time('H:i')
                            ->placeholder('—'),

                        TextEntry::make('partner.company_name')
                            ->label('Partner')
                            ->placeholder('Individual Booking'),
                    ])->columnSpanFull(),

                // ── PAX Overview Banner ───────────────────────────────────────
                Section::make('PAX Overview')
                    ->description('Breakdown of passengers for this booking. Use the attendance table below to mark each passenger, or use the "Set Showed Count" override if passenger details are incomplete.')
                    ->icon('heroicon-o-user-group')
                    ->columns(6)
                    ->components([
                        TextEntry::make('pax_adults')
                            ->label('👤 Adults')
                            ->getStateUsing(fn (Booking $record): string => (string) $record->adult_pax)
                            ->badge()
                            ->color('info'),

                        TextEntry::make('pax_children')
                            ->label('👶 Children')
                            ->getStateUsing(fn (Booking $record): string => (string) $record->child_pax)
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('pax_total')
                            ->label('🧳 Total PAX')
                            ->getStateUsing(fn (Booking $record): string => (string) $record->getTotalPax())
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('pax_filed')
                            ->label('📋 Records Filed')
                            ->getStateUsing(fn (Booking $record): string => $record->getFiledCustomerCount() . ' / ' . $record->getTotalPax())
                            ->badge()
                            ->color(fn (Booking $record): string => $record->getFiledCustomerCount() >= $record->getTotalPax() ? 'success' : 'gray'),

                        TextEntry::make('pax_showed')
                            ->label('✅ Showed')
                            ->getStateUsing(fn (Booking $record): string => $record->getShowedPax() . ' / ' . $record->getTotalPax())
                            ->badge()
                            ->color(fn (Booking $record): string => match (true) {
                                $record->getShowedPax() === $record->getTotalPax() && $record->getTotalPax() > 0 => 'success',
                                $record->getShowedPax() > 0                                                      => 'warning',
                                default                                                                          => 'gray',
                            }),

                        TextEntry::make('pax_override')
                            ->label('🎯 Override Active')
                            ->getStateUsing(fn (Booking $record): string => $record->attended_pax !== null
                                ? 'Yes — ' . $record->attended_pax . ' PAX'
                                : 'No — using row data'
                            )
                            ->badge()
                            ->color(fn (Booking $record): string => $record->attended_pax !== null ? 'warning' : 'gray'),
                    ])->columnSpanFull(),

                // ── Transport Assignment ──────────────────────────────────────
                Section::make('Transport Assignment')
                    ->icon('heroicon-o-truck')
                    ->visible(fn ($record): bool => $record->dispatch?->dispatchDriverRows->isNotEmpty() ?? false)
                    ->components([
                        RepeatableEntry::make('dispatch_rows')
                            ->label('')
                            ->getStateUsing(
                                fn ($record) => $record->dispatch?->dispatchDriverRows->load(['vehicle', 'driver']) ?? collect()
                            )
                            ->columns(4)
                            ->schema([
                                TextEntry::make('vehicle.make')
                                    ->label('Vehicle')
                                    ->icon('heroicon-o-truck')
                                    ->formatStateUsing(fn ($state, $record) =>
                                        trim(($record->vehicle?->make ?? '') . ' ' . ($record->vehicle?->model ?? '')) ?: '—'
                                    ),

                                TextEntry::make('vehicle.plate_number')
                                    ->label('License Plate')
                                    ->icon('heroicon-o-identification')
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('—'),

                                TextEntry::make('driver.name')
                                    ->label('Driver')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('—'),

                                TextEntry::make('driver.phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('—'),
                            ])
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }
}
