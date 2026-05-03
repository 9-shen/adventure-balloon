<?php

namespace App\Filament\Admin\Resources\DeletionRecords\ProductResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\ProductResource;
use Filament\Resources\Pages\ManageRecords;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}