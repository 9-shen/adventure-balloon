<?php

namespace App\Filament\Admin\Resources\DeletionRecords\VehicleResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\VehicleResource;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicles extends ManageRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}