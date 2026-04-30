<?php

namespace App\Filament\Dispatcher\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\Bookings\Pages\CreateBooking as BasePage;
use App\Filament\Dispatcher\Resources\BookingResource;

class CreateBooking extends BasePage
{
    protected static string $resource = BookingResource::class;
}
