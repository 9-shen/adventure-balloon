<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('bank.bank_name',        null);
        $this->migrator->add('bank.bank_holder_name', null);
        $this->migrator->add('bank.bank_account',     null);
        $this->migrator->add('bank.iban',             null);
        $this->migrator->add('bank.swift',            null);
        $this->migrator->add('bank.routing_number',   null);
    }
};
