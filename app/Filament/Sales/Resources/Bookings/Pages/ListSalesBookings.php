<?php

namespace App\Filament\Sales\Resources\Bookings\Pages;

use App\Filament\Sales\Resources\Bookings\SalesBookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesBookings extends ListRecords
{
    protected static string $resource = SalesBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New Booking')];
    }
}
