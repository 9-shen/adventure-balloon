<?php

namespace App\Filament\Admin\Resources\TransportCompanies\Pages;

use App\Filament\Admin\Resources\TransportCompanies\TransportCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransportCompanies extends ListRecords
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
