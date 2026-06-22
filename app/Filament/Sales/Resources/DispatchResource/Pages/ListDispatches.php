<?php

namespace App\Filament\Sales\Resources\DispatchResource\Pages;

use App\Filament\Sales\Resources\DispatchResource;
use Filament\Resources\Pages\ListRecords;

class ListDispatches extends ListRecords
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
