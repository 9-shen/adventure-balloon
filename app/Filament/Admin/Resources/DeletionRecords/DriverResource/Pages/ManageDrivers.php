<?php

namespace App\Filament\Admin\Resources\DeletionRecords\DriverResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\DriverResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDrivers extends ManageRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}