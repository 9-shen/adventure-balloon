<?php

namespace App\Filament\Partner\Resources\Invoices\Pages;

use App\Filament\Partner\Resources\Invoices\PartnerInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListPartnerInvoices extends ListRecords
{
    protected static string $resource = PartnerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Partners cannot create invoices — admin only
    }
}
