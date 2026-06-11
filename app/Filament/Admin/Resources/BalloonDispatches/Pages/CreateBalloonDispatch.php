<?php

namespace App\Filament\Admin\Resources\BalloonDispatches\Pages;

use App\Filament\Admin\Resources\BalloonDispatches\BalloonDispatchResource;
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
