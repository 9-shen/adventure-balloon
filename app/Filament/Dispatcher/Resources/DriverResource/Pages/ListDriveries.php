<?php

namespace App\Filament\Dispatcher\Resources\DriverResource\Pages;

use App\Filament\Dispatcher\Resources\DriverResource;
use Filament\Resources\Pages\ListRecords;

class ListDrivers extends ListRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
