<?php

namespace App\Filament\Admin\Resources\BalloonDispatches\Pages;

use App\Filament\Admin\Resources\BalloonDispatches\BalloonDispatchResource;
use App\Filament\BalloonDispatcher\Widgets\BalloonDispatchPaxTodayWidget;
use Filament\Resources\Pages\ListRecords;

class ListBalloonDispatches extends ListRecords
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BalloonDispatchPaxTodayWidget::class,
        ];
    }
}
