<?php

namespace App\Filament\Guide\Resources\DispatchResource\Pages;

use App\Filament\Guide\Resources\DispatchResource;
use Filament\Resources\Pages\ListRecords;

class ListDispatches extends ListRecords
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Read-only — no create
    }
}
