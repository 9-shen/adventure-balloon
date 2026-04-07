<?php

namespace App\Filament\Admin\Resources\Dispatches\Pages;

use App\Filament\Admin\Resources\Dispatches\DispatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListDispatches extends ListRecords
{
    protected static string $resource = DispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
