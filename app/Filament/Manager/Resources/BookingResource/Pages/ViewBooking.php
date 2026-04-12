<?php

namespace App\Filament\Manager\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\Bookings\Pages\ViewBooking as BasePage;
use App\Filament\Manager\Resources\BookingResource;

class ViewBooking extends BasePage
{
    protected static string $resource = BookingResource::class;
}
