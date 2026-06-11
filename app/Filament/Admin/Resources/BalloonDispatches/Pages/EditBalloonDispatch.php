<?php

namespace App\Filament\Admin\Resources\BalloonDispatches\Pages;

use App\Filament\Admin\Resources\BalloonDispatches\BalloonDispatchResource;
use Filament\Resources\Pages\EditRecord;

class EditBalloonDispatch extends EditRecord
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
