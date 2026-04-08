<?php

namespace App\Filament\Admin\Resources\Greeter\Pages;

use App\Filament\Admin\Resources\Greeter\GreeterBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListGreeterBookings extends ListRecords
{
    protected static string $resource = GreeterBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];  // greeter cannot create bookings
    }
}
