<?php

namespace App\Filament\Admin\Resources\TransportCompanies\Pages;

use App\Filament\Admin\Resources\TransportCompanies\TransportCompanyResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransportCompany extends ViewRecord
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            RestoreAction::make(),
            DeleteAction::make(),
        ];
    }
}
