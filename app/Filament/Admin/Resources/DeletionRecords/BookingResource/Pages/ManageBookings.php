<?php

namespace App\Filament\Admin\Resources\DeletionRecords\BookingResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\BookingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBookings extends ManageRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}