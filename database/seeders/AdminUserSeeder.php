<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'webmaster@9-shen.com'],
            [
                'name'              => 'Booklix Admin',
                'email'             => 'webmaster@9-shen.com',
                'password'          => Hash::make('Nou@man001'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('super_admin');

        $this->command->info('✅ Super admin created: webmaster@9-shen.com / Nou@man001!');
    }
}
