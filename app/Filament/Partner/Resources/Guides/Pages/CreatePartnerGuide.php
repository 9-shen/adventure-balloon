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

    protected function afterCreate(): void
    {
        $guide = $this->record;
        $this->createGuidePortalAccount($guide);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function createGuidePortalAccount(Guide $guide): void
    {
        $rawPassword = '1234567890';

        $user = User::firstOrCreate(
            ['email' => $guide->email],
            [
                'name'       => $guide->name,
                'password'   => Hash::make($rawPassword),
                'phone'      => $guide->phone,
                'is_active'  => true,
                'guide_id'   => $guide->id,
                'partner_id' => $guide->partner_id,
            ]
        );

        if (! $user->hasRole('guide')) {
            $user->assignRole('guide');
        }

        try {
            $guide->notify(new GuideAccountCreatedNotification($guide->name, $guide->email, $rawPassword));
        } catch (\Exception $e) {
            Log::error("CreatePartnerGuide: failed to notify guide [{$guide->id}]: " . $e->getMessage());
        }
    }
}
