<?php

namespace App\Filament\Manager\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\Bookings\Pages\ListBookings as BasePage;
use App\Filament\Manager\Resources\BookingResource;

class ListBookings extends BasePage
{
    protected static string $resource = BookingResource::class;
}
