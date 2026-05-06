<?php

namespace App\Filament\Admin\Resources\BalloonDispatchers\Pages;

use App\Filament\Admin\Resources\BalloonDispatchers\BalloonDispatcherResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBalloonDispatcher extends CreateRecord
{
    protected static string $resource = BalloonDispatcherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
