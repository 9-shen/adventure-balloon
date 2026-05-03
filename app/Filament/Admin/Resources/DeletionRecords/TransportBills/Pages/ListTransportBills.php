<?php

namespace App\Filament\Admin\Resources\DeletionRecords\TransportBills\Pages;

use App\Filament\Admin\Resources\DeletionRecords\TransportBills\TransportBillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransportBills extends ListRecords
{
    protected static string $resource = TransportBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

