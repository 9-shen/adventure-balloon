<?php

namespace App\Filament\Transport\Resources\Drivers\Pages;

use App\Filament\Transport\Resources\Drivers\DriverResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data['transport_company_id'] = $user->transport_company_id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $driver = $this->record;

        if ($driver->email) {
            $rawPassword = '1234567890';
            
            $user = \App\Models\User::firstOrCreate(
                ['email' => $driver->email],
                [
                    'name' => $driver->name,
                    'password' => \Illuminate\Support\Facades\Hash::make($rawPassword),
                    'phone' => $driver->phone,
                    'is_active' => true,
                    'driver_id' => $driver->id,
                    'transport_company_id' => $driver->transport_company_id,
                ]
            );

            if (!$user->hasRole('driver')) {
                $user->assignRole('driver');
            }

            try {
                $driver->notify(new \App\Notifications\DriverAccountCreatedNotification($driver->name, $driver->email, $rawPassword));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to notify driver: " . $e->getMessage());
            }
        }
    }
}
