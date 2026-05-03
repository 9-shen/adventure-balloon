<?php

namespace App\Filament\Admin\Resources\DeletionRecords\TransportBills\Pages;

use App\Filament\Admin\Resources\DeletionRecords\TransportBills\TransportBillResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTransportBill extends EditRecord
{
    protected static string $resource = TransportBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

