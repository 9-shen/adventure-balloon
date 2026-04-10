<?php

namespace App\Filament\Partner\Resources\Bookings\Pages;

use App\Filament\Partner\Resources\Bookings\PartnerBookingResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPartnerBooking extends ViewRecord
{
    protected static string $resource = PartnerBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to My Bookings')
                ->url(PartnerBookingResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Booking Details')
                ->columns(2)
                ->schema([
                    TextEntry::make('booking_ref')->label('Reference')->copyable()->weight('bold'),
                    TextEntry::make('booking_status')->label('Status')->badge(),
                    TextEntry::make('product.name')->label('Experience'),
                    TextEntry::make('flight_date')->label('Flight Date')->date('d/m/Y'),
                    TextEntry::make('flight_time')->label('Flight Time')->time('H:i'),
                    TextEntry::make('adult_pax')->label('Adults'),
                    TextEntry::make('child_pax')->label('Children'),
                    TextEntry::make('notes')->label('Notes')->columnSpanFull(),
                ]),

            Section::make('Financials')
                ->columns(2)
                ->schema([
                    TextEntry::make('final_amount')->label('Total Amount')->money('MAD'),
                    TextEntry::make('amount_paid')->label('Amount Paid')->money('MAD'),
                    TextEntry::make('balance_due')->label('Balance Due')->money('MAD'),
                    TextEntry::make('payment_status')->label('Payment Status')->badge(),
                ]),
        ]);
    }
}
