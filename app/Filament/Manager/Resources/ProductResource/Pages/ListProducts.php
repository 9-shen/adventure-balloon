<?php

namespace App\Filament\Manager\Resources\ProductResource\Pages;

use App\Filament\Manager\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
