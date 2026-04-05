<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('whatsapp.account_sid', null);
        $this->migrator->add('whatsapp.auth_token',  null);
        $this->migrator->add('whatsapp.from_number', null);
        $this->migrator->add('whatsapp.enabled',     false);
    }
};
