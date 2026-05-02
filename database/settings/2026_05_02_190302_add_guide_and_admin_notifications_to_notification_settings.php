<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notifications.booking_confirmed_guide_email', false);
        $this->migrator->add('notifications.booking_cancelled_guide_email', false);
        $this->migrator->add('notifications.booking_cancelled_admin_email', false);
    }

    public function down(): void
    {
        $this->migrator->delete('notifications.booking_confirmed_guide_email');
        $this->migrator->delete('notifications.booking_cancelled_guide_email');
        $this->migrator->delete('notifications.booking_cancelled_admin_email');
    }
};
