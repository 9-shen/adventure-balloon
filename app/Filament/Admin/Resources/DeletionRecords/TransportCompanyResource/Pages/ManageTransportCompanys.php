<?php

namespace App\Filament\Admin\Resources\DeletionRecords\TransportCompanyResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\TransportCompanyResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTransportCompanys extends ManageRecords
{
    protected static string $resource = TransportCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}