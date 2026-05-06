<?php

namespace App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\Pages;

use App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\BalloonDispatchResource;
use Filament\Resources\Pages\EditRecord;

class EditBalloonDispatch extends EditRecord
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
