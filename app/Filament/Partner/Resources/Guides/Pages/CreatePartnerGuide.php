<?php

namespace App\Filament\Partner\Resources\Guides\Pages;

use App\Filament\Partner\Resources\Guides\PartnerGuideResource;
use App\Models\Guide;
use App\Models\User;
use App\Notifications\GuideAccountCreatedNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CreatePartnerGuide extends CreateRecord
{
    protected static string $resource = PartnerGuideResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['partner_id'] = Auth::user()->partner_id;
        return $data;
    }


}
