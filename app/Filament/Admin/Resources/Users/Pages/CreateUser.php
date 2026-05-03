<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = static::getModel()::create($data);
        
        $user->partner_id = $data['partner_id'] ?? null;
        $user->transport_company_id = $data['transport_company_id'] ?? null;
        $user->driver_id = $data['driver_id'] ?? null;
        $user->guide_id = $data['guide_id'] ?? null;
        $user->save();

        try {
            $user->notify(new \App\Notifications\UserAccountCreatedNotification($user->name, $user->email));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to notify user: " . $e->getMessage());
        }

        return $user;
    }
}
