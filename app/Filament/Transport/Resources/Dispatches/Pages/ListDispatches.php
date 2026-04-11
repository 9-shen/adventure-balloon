<?php

namespace App\Filament\Transport\Resources\Dispatches\Pages;

use App\Filament\Transport\Resources\Dispatches\DispatchResource;
use Filament\Resources\Pages\ListRecords;

class ListDispatches extends ListRecords
{
    protected static string $resource = DispatchResource::class;
}
