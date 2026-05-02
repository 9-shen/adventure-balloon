<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notifications.invoice_issued_partner_email', true);
        $this->migrator->add('notifications.transport_bill_transport_company_email', true);
    }

    public function down(): void
    {
        $this->migrator->delete('notifications.invoice_issued_partner_email');
        $this->migrator->delete('notifications.transport_bill_transport_company_email');
    }
};
