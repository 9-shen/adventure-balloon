<?php

namespace App\Filament\Manager\Resources\BalloonDispatchResource\Pages;

use App\Filament\Admin\Resources\BalloonDispatches\Pages\ViewBalloonDispatch as BasePage;
use App\Filament\Manager\Resources\BalloonDispatchResource;

class ViewBalloonDispatch extends BasePage
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter(parent::getHeaderActions(), function ($action) {
            return $action->getName() === 'download_image';
        });
    }
}
