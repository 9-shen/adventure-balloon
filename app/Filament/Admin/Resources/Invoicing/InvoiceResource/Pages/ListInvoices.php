<?php

namespace App\Filament\Admin\Resources\Invoicing\InvoiceResource\Pages;

use App\Filament\Admin\Resources\Invoicing\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}
