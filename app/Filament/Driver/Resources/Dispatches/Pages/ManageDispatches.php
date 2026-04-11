<?php

namespace App\Filament\Driver\Resources\Dispatches\Pages;

use App\Filament\Driver\Resources\Dispatches\DispatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDispatches extends ManageRecords
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
