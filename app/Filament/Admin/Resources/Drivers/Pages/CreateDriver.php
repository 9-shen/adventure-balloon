<?php

namespace App\Filament\Admin\Resources\Drivers\Pages;

use App\Filament\Admin\Resources\Drivers\DriverResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;

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
