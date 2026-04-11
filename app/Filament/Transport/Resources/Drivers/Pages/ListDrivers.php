<?php

namespace App\Filament\Transport\Resources\Drivers\Pages;

use App\Filament\Transport\Resources\Drivers\DriverResource;
use Filament\Resources\Pages\ListRecords;

class ListDrivers extends ListRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
