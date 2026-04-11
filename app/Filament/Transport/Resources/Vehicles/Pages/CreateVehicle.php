<?php

namespace App\Filament\Transport\Resources\Vehicles\Pages;

use App\Filament\Transport\Resources\Vehicles\VehicleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data['transport_company_id'] = $user->transport_company_id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
