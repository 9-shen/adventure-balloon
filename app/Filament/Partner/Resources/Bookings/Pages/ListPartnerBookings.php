<?php

namespace App\Filament\Partner\Resources\Bookings\Pages;

use App\Filament\Partner\Resources\Bookings\PartnerBookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerBookings extends ListRecords
{
    protected static string $resource = PartnerBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Booking'),
        ];
    }
}
