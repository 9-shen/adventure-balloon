<?php

namespace App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\Pages;

use App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\BalloonDispatchResource;
use App\Filament\BalloonDispatcher\Widgets\BalloonDispatchPaxTodayWidget;
use Filament\Resources\Pages\ListRecords;

class ListBalloonDispatches extends ListRecords
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            BalloonDispatchPaxTodayWidget::class,
        ];
    }
}
