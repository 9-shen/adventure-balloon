<?php

namespace App\Filament\Admin\Resources\BalloonDispatchers\Pages;

use App\Filament\Admin\Resources\BalloonDispatchers\BalloonDispatcherResource;
use Filament\Resources\Pages\EditRecord;

class EditBalloonDispatcher extends EditRecord
{
    protected static string $resource = BalloonDispatcherResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
