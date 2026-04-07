<?php

namespace App\Filament\Admin\Resources\Dispatches\Pages;

use App\Filament\Admin\Resources\Dispatches\DispatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDispatch extends ViewRecord
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
