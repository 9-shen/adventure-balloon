<?php

namespace App\Filament\Admin\Resources\DeletionRecords\InvoiceResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\InvoiceResource;
use Filament\Resources\Pages\ManageRecords;

class ManageInvoices extends ManageRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}