<?php

namespace App\Filament\Dispatcher\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\Bookings\Pages\ListBookings as BasePage;
use App\Filament\Dispatcher\Resources\BookingResource;

class ListBookings extends BasePage
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
