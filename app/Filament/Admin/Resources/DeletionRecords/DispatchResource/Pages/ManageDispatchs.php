<?php

namespace App\Filament\Admin\Resources\DeletionRecords\DispatchResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\DispatchResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDispatchs extends ManageRecords
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}