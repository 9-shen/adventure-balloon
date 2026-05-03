<?php

namespace App\Filament\Admin\Resources\DeletionRecords\TransportBillResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\TransportBillResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTransportBills extends ManageRecords
{
    protected static string $resource = TransportBillResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}