<?php

namespace App\Filament\Manager\Resources\GreeterBookingResource\Pages;

use App\Filament\Manager\Resources\GreeterBookingResource;
use App\Filament\Greeter\Widgets\GreeterStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListManagerGreeterBookings extends ListRecords
{
    protected static string $resource = GreeterBookingResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Manager views attendance only — no creation from here
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GreeterStatsWidget::class,
        ];
    }
}
