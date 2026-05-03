<?php

namespace App\Filament\Admin\Resources\DeletionRecords\GuideResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\GuideResource;
use Filament\Resources\Pages\ManageRecords;

class ManageGuides extends ManageRecords
{
    protected static string $resource = GuideResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}