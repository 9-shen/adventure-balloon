<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('legal.identifiant_fiscal', null);
        $this->migrator->add('legal.cnss_number',        null);
        $this->migrator->add('legal.patente_number',     null);
        $this->migrator->add('legal.registre_commerce',  null);
        $this->migrator->add('legal.ice_number',         null);
    }
};
