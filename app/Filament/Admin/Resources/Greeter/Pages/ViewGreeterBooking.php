<?php

namespace App\Filament\Admin\Resources\Greeter\Pages;

use App\Filament\Admin\Resources\Greeter\GreeterBookingResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

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

                // ── Transport Assignment ─────────────────────────────────────────
                Section::make('Transport Assignment')
                    ->icon('heroicon-o-truck')
                    ->columns(2)
                    ->visible(fn ($record): bool => $record->dispatch?->dispatchDriverRows->isNotEmpty() ?? false)
                    ->components([
                        // Vehicle
                        TextEntry::make('vehicle_name')
                            ->label('Vehicle')
                            ->icon('heroicon-o-truck')
                            ->getStateUsing(function ($record): string {
                                $row = $record->dispatch?->dispatchDriverRows->first();
                                if (!$row?->vehicle) return '—';
                                return trim(($row->vehicle->make ?? '') . ' ' . ($row->vehicle->model ?? ''));
                            })
                            ->placeholder('—'),

                        TextEntry::make('vehicle_plate')
                            ->label('License Plate')
                            ->icon('heroicon-o-identification')
                            ->badge()
                            ->color('gray')
                            ->getStateUsing(function ($record): string {
                                $row = $record->dispatch?->dispatchDriverRows->first();
                                return $row?->vehicle?->plate_number ?? '—';
                            })
                            ->placeholder('—'),

                        // Driver
                        TextEntry::make('driver_name')
                            ->label('Driver')
                            ->icon('heroicon-o-user')
                            ->getStateUsing(function ($record): string {
                                $row = $record->dispatch?->dispatchDriverRows->first();
                                return $row?->driver?->name ?? '—';
                            })
                            ->placeholder('—'),

                        TextEntry::make('driver_phone')
                            ->label('Driver Phone')
                            ->icon('heroicon-o-phone')
                            ->getStateUsing(function ($record): string {
                                $row = $record->dispatch?->dispatchDriverRows->first();
                                return $row?->driver?->phone ?? '—';
                            })
                            ->placeholder('—'),
                    ])->columnSpanFull(),
            ]);
    }
}
