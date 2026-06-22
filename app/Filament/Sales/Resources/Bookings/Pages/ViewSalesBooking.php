<?php

namespace App\Filament\Sales\Resources\Bookings\Pages;

use App\Filament\Sales\Resources\Bookings\SalesBookingResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewSalesBooking extends ViewRecord
{
    protected static string $resource = SalesBookingResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist->components([
            Section::make('Booking Summary')
                ->columns(4)
                ->components([
                    TextEntry::make('booking_ref')->label('Ref')->badge()->color('primary')->copyable(),
                    TextEntry::make('booking_status')->label('Status')->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'confirmed' => 'success',
                            'cancelled' => 'danger',
                            'completed' => 'info',
                            default     => 'warning',
                        }),
                    TextEntry::make('product.name')->label('Experience'),
                    TextEntry::make('flight_date')->label('Date')->date('d/m/Y'),
                    TextEntry::make('adult_pax')->label('Adults'),
                    TextEntry::make('child_pax')->label('Children'),
                    TextEntry::make('booking_source')->label('Booking Source')->placeholder('—'),
                ]),

            Section::make('Pricing & Financials')
                ->columns(3)
                ->components([
                    TextEntry::make('base_adult_price')
                        ->label('Adult Price')
                        ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency()),
                    TextEntry::make('base_child_price')
                        ->label('Child Price')
                        ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency()),
                    TextEntry::make('adult_total')
                        ->label('Adult Total')
                        ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency()),
                    TextEntry::make('child_total')
                        ->label('Child Total')
                        ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency()),
                    TextEntry::make('discount_amount')
                        ->label('Discount Amount')
                        ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency()),
                    TextEntry::make('discount_reason')
                        ->label('Discount Reason')
                        ->placeholder('No discount applied'),
                    TextEntry::make('final_amount')
                        ->label('Final Amount')
                        ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency())
                        ->weight('bold'),
                    TextEntry::make('payment_method')->label('Payment Method')->badge(),
                    TextEntry::make('payment_status')
                        ->label('Payment Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'paid'    => 'success',
                            'partial' => 'warning',
                            'on_site' => 'info',
                            default   => 'danger',
                        }),
                ]),

            Section::make('Transportation Details')
                ->columns(3)
                ->components([
                    TextEntry::make('pickup_location')->label('Pick-up Location')->placeholder('—'),
                    TextEntry::make('pickup_map_link')
                        ->label('Pick-up Map Link')
                        ->placeholder('—')
                        ->icon('heroicon-o-map')
                        ->formatStateUsing(fn ($state) => 'Open Google Maps')
                        ->url(fn ($state) => $state)
                        ->openUrlInNewTab()
                        ->color('primary')
                        ->badge(),
                    TextEntry::make('dropoff_location')->label('Drop-off Location')->placeholder('—'),
                ]),

            Section::make('Passengers')
                ->components([
                    RepeatableEntry::make('customers')
                        ->label('Passenger manifest')
                        ->columns(4)
                        ->schema([
                            TextEntry::make('full_name')->label('Name')->weight('medium'),
                            TextEntry::make('type')->label('Type')->badge()->formatStateUsing(fn (string $state) => ucfirst($state)),
                            TextEntry::make('phone')->label('Phone')->placeholder('—'),
                            TextEntry::make('weight_kg')->label('Weight')->suffix(' kg')->placeholder('—'),
                        ]),
                ]),
                
            Section::make('Internal Notes')
                ->components([
                    TextEntry::make('notes')
                        ->label('')
                        ->placeholder('No internal notes recorded')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
