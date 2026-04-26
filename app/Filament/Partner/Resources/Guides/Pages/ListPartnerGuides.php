<?php

namespace App\Filament\Partner\Resources\Guides\Pages;

use App\Filament\Partner\Resources\Guides\PartnerGuideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerGuides extends ListRecords
{
    protected static string $resource = PartnerGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
