<?php

namespace App\Filament\Guide\Resources\Bookings\Pages;

use App\Filament\Guide\Resources\Bookings\GuideBookingResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;

class ViewGuideBooking extends ViewRecord
{
    protected static string $resource = GuideBookingResource::class;

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
                    TextEntry::make('final_amount')->label('Total')->money(),
                    TextEntry::make('partner_reference')->label('Your Ref')->placeholder('—'),
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
                ])->columnSpanFull(),
        ]);
    }
}
