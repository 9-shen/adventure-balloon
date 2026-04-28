<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ── Partner Booking Alert ───────────────────────────────────────────
        $this->migrator->add('notifications.partner_booking_email', true);

        // ── Driver Dispatch Assignment ──────────────────────────────────────
        $this->migrator->add('notifications.driver_assigned_email',    true);
        $this->migrator->add('notifications.driver_assigned_whatsapp', true);

        // ── Booking Cancellation ────────────────────────────────────────────
        $this->migrator->add('notifications.booking_cancelled_partner_email',   true);
        $this->migrator->add('notifications.booking_cancelled_transport_email', true);
        $this->migrator->add('notifications.booking_cancelled_driver_email',    true);
        $this->migrator->add('notifications.booking_cancelled_driver_whatsapp', true);

        // ── PAX Capacity Alerts ─────────────────────────────────────────────
        $this->migrator->add('notifications.pax_alert_email',    true);
        $this->migrator->add('notifications.pax_alert_whatsapp', true);
    }
};
