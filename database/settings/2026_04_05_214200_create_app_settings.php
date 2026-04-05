<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.company_name',    'Booklix');
        $this->migrator->add('app.company_email',   'info@booklix.com');
        $this->migrator->add('app.company_phone',   '+212 600 000 000');
        $this->migrator->add('app.company_address', 'Marrakech, Morocco');
        $this->migrator->add('app.logo_path',       null);
    }
};
