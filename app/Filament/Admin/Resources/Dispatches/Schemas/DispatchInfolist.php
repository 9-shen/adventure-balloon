<?php

namespace App\Filament\Admin\Resources\Dispatches\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DispatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── Dispatch Details ──────────────────────────────────────────────
            Section::make('Dispatch Details')
                ->columns(3)
                ->components([
                    TextEntry::make('dispatch_ref')
                        ->label('Reference')
                        ->badge()
                        ->color('warning'),

                    TextEntry::make('booking.booking_ref')
                        ->label('Booking')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'confirmed'   => 'success',
                            'in_progress' => 'warning',
                            'delivered'   => 'info',
                            'cancelled'   => 'danger',
                            default       => 'gray',
                        })
                        ->formatStateUsing(fn(string $state): string => match ($state) {
                            'in_progress' => 'In Progress',
                            default       => ucfirst($state),
                        }),

                    TextEntry::make('transportCompany.company_name')
                        ->label('Transport Company'),

                    TextEntry::make('flight_date')
                        ->label('Flight Date')
                        ->date('d/m/Y'),

                    TextEntry::make('total_pax')
                        ->label('Total PAX'),

                    TextEntry::make('pickup_time')
                        ->label('Pickup Time')
                        ->time('H:i')
                        ->placeholder('Not set'),

                    TextEntry::make('pickup_location')
                        ->label('Pickup Location')
                        ->placeholder('Not set'),

                    TextEntry::make('dropoff_location')
                        ->label('Dropoff Location')
                        ->placeholder('Not set'),
                ]),

            // ── Booking Link ─────────────────────────────────────────────────
            Section::make('Booking Details')
                ->columns(3)
                ->components([
                    TextEntry::make('booking.booking_ref')
                        ->label('Booking Ref'),

                    TextEntry::make('booking.flight_date')
                        ->label('Flight Date')
                        ->date('d/m/Y'),

                    TextEntry::make('booking.adult_pax')
                        ->label('Adults'),

                    TextEntry::make('booking.child_pax')
                        ->label('Children'),

                    TextEntry::make('booking.booking_status')
                        ->label('Booking Status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'confirmed'  => 'success',
                            'cancelled'  => 'danger',
                            'completed'  => 'info',
                            default      => 'warning',
                        })
                        ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                    TextEntry::make('booking.product.name')
                        ->label('Product'),
                ]),

            // ── Driver Assignments ────────────────────────────────────────────
            Section::make('Driver Assignments')
                ->components([
                    RepeatableEntry::make('dispatchDriverRows')
                        ->label('')
                        ->columns(4)
                        ->schema([
                            TextEntry::make('driver.name')
                                ->label('Driver'),

                            TextEntry::make('vehicle.make')
                                ->label('Vehicle Make'),

                            TextEntry::make('vehicle.model')
                                ->label('Vehicle Model'),

                            TextEntry::make('pax_assigned')
                                ->label('PAX'),
                        ]),
                ]),

            // ── Notifications ─────────────────────────────────────────────────
            Section::make('Notifications')
                ->columns(2)
                ->components([
                    TextEntry::make('notified_at')
                        ->label('Transporter Notified At')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Not sent yet'),

                    TextEntry::make('createdBy.name')
                        ->label('Created By')
                        ->placeholder('—'),
                ]),

            // ── Notes ────────────────────────────────────────────────────────
            Section::make('Notes')
                ->collapsible()
                ->components([
                    TextEntry::make('notes')
                        ->label('')
                        ->columnSpanFull()
                        ->placeholder('No notes.'),
                ])->columnSpanFull(),
        ]);
    }
}
