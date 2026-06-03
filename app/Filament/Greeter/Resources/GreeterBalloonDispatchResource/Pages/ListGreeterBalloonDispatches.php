<?php

namespace App\Filament\Greeter\Resources\GreeterBalloonDispatchResource\Pages;

use App\Filament\Greeter\Resources\GreeterBalloonDispatchResource;
use Filament\Resources\Pages\ListRecords;

class ListGreeterBalloonDispatches extends ListRecords
{
    protected static string $resource = GreeterBalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return []; // no create button
    }
}
