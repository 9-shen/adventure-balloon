<?php

namespace App\Filament\Greeter\Resources\GreeterBookingResource\Pages;

use App\Filament\Greeter\Resources\GreeterBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListGreeterBookings extends ListRecords
{
    protected static string $resource = GreeterBookingResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Greeters cannot create bookings
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Greeter\Widgets\GreeterStatsWidget::class,
        ];
    }
}
