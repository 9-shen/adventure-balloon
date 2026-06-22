<?php

namespace App\Filament\Sales\Resources\DispatchResource\Pages;

use App\Filament\Sales\Resources\DispatchResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDispatch extends ViewRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
