<?php

namespace App\Filament\Transport\Resources\Drivers\Pages;

use App\Filament\Transport\Resources\Drivers\DriverResource;

use Filament\Resources\Pages\EditRecord;

class EditDriver extends EditRecord
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
