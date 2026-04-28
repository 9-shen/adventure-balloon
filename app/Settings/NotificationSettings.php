<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Controls which notification channels are enabled per event type.
 * Each property follows the pattern: {event}_{channel}
 */
class NotificationSettings extends Settings
{
    // ── Partner Booking (admin alert when a partner books) ──────────────────
    public bool $partner_booking_email;       // Email to admin

    // ── Driver Dispatch Assignment ──────────────────────────────────────────
    public bool $driver_assigned_email;       // Email to driver
    public bool $driver_assigned_whatsapp;    // WhatsApp to driver

    // ── Booking Cancellation (partner booking) ──────────────────────────────
    public bool $booking_cancelled_partner_email;    // Email to partner
    public bool $booking_cancelled_transport_email;  // Email to transport company
    public bool $booking_cancelled_driver_email;     // Email to each driver
    public bool $booking_cancelled_driver_whatsapp;  // WhatsApp to each driver

    // ── PAX Capacity Alerts ─────────────────────────────────────────────────
    public bool $pax_alert_email;             // Email to admin
    public bool $pax_alert_whatsapp;          // WhatsApp to admin

    public static function group(): string
    {
        return 'notifications';
    }
}
