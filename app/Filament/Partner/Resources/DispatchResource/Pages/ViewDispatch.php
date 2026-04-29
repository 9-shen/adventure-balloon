<?php

namespace App\Filament\Partner\Resources\DispatchResource\Pages;

use App\Filament\Partner\Resources\DispatchResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDispatch extends ViewRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Read-only — no edit/delete
    }
}
