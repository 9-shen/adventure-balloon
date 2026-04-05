<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pax.daily_pax_capacity', 250);
        $this->migrator->add('pax.warning_threshold',  20);
    }
};
