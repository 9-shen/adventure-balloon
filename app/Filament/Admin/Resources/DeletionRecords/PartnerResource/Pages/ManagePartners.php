<?php

namespace App\Filament\Admin\Resources\DeletionRecords\PartnerResource\Pages;

use App\Filament\Admin\Resources\DeletionRecords\PartnerResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePartners extends ManageRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}