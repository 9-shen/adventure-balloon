<?php

namespace App\Filament\Dispatcher\Resources\VehicleResource\Pages;

use App\Filament\Dispatcher\Resources\VehicleResource;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
