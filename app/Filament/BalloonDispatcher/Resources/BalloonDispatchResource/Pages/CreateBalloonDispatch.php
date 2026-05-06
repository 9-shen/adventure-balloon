<?php

namespace App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\Pages;

use App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource\BalloonDispatchResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBalloonDispatch extends CreateRecord
{
    protected static string $resource = BalloonDispatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
