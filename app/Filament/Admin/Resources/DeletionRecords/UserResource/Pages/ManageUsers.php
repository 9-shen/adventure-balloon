<?php

namespace App\Filament\Admin\Resources\DeletionRecords\UserResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}