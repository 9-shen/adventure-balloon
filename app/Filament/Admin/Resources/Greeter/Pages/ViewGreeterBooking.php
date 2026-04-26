<?php

namespace App\Filament\Admin\Resources\Greeter\Pages;

use App\Filament\Admin\Resources\Greeter\GreeterBookingResource;
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
                // ── Booking Summary ──────────────────────────────────────────────
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
                            ->color('info'),

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

                // ── Transport Assignment — native Filament RepeatableEntry ────────
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
