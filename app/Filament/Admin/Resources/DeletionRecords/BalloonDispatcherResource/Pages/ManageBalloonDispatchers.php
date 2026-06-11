<?php

namespace App\Filament\Admin\Resources\DeletionRecords\BalloonDispatcherResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\BalloonDispatcherResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBalloonDispatchers extends ManageRecords
{
    protected static string $resource = BalloonDispatcherResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
