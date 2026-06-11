<?php

namespace App\Filament\Manager\Resources\BalloonDispatchResource\Pages;

use App\Filament\Admin\Resources\BalloonDispatches\Pages\ListBalloonDispatches as BasePage;
use App\Filament\Manager\Resources\BalloonDispatchResource;

class ListBalloonDispatches extends BasePage
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
