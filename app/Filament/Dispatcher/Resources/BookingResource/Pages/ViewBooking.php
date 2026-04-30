<?php

namespace App\Filament\Dispatcher\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\Bookings\Pages\ViewBooking as BasePage;
use App\Filament\Dispatcher\Resources\BookingResource;

class ViewBooking extends BasePage
{
    protected static string $resource = BookingResource::class;
}
