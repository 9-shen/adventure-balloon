<?php

namespace App\Filament\Manager\Resources\PartnerResource\Pages;

use App\Filament\Admin\Resources\Partners\Pages\ListPartners as BasePage;
use App\Filament\Manager\Resources\PartnerResource;

class ListPartners extends BasePage
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
