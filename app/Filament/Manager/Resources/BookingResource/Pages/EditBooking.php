<?php

namespace App\Filament\Manager\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\Bookings\Pages\EditBooking as BasePage;
use App\Filament\Manager\Resources\BookingResource;

class EditBooking extends BasePage
{
    protected static string $resource = BookingResource::class;
}
