<?php

namespace App\Filament\Dispatcher\Resources\TransportCompanyResource\Pages;

use App\Filament\Dispatcher\Resources\TransportCompanyResource;
use Filament\Resources\Pages\ListRecords;

class ListTransportCompanies extends ListRecords
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
