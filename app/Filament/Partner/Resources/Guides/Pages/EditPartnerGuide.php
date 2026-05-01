<?php

namespace App\Filament\Partner\Resources\Guides\Pages;

use App\Filament\Partner\Resources\Guides\PartnerGuideResource;

use Filament\Resources\Pages\EditRecord;

class EditPartnerGuide extends EditRecord
{
    protected static string $resource = PartnerGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
