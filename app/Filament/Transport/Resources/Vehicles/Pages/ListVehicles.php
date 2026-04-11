<?php

namespace App\Filament\Transport\Resources\Vehicles\Pages;

use App\Filament\Transport\Resources\Vehicles\VehicleResource;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
