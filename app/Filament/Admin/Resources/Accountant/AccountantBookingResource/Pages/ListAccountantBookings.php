<?php

namespace App\Filament\Admin\Resources\Accountant\AccountantBookingResource\Pages;

use App\Filament\Admin\Resources\Accountant\AccountantBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountantBookings extends ListRecords
{
    protected static string $resource = AccountantBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No automated creation by accountants; they just manage finances.
        ];
    }
}
