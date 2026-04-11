<?php

namespace App\Filament\Transport\Resources\TransportBillResource\Pages;

use App\Filament\Transport\Resources\TransportBillResource;
use Filament\Resources\Pages\ListRecords;

class ListTransportBills extends ListRecords
{
    protected static string $resource = TransportBillResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
