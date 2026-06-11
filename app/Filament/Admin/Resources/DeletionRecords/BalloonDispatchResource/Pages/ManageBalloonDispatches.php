<?php

namespace App\Filament\Admin\Resources\DeletionRecords\BalloonDispatchResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\BalloonDispatchResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBalloonDispatches extends ManageRecords
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
