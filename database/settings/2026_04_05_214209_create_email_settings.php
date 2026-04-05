<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email.host',         'smtp.mailtrap.io');
        $this->migrator->add('email.port',         587);
        $this->migrator->add('email.username',     null);
        $this->migrator->add('email.password',     null);
        $this->migrator->add('email.encryption',   'tls');
        $this->migrator->add('email.from_address', 'noreply@booklix.com');
        $this->migrator->add('email.from_name',    'Booklix');
    }
};
