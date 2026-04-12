<?php

namespace App\Filament\Manager\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\Bookings\Pages\CreateBooking as BasePage;
use App\Filament\Manager\Resources\BookingResource;

class CreateBooking extends BasePage
{
    protected static string $resource = BookingResource::class;
}
