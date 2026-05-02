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

    // ── Booking Confirmation (partner notified when booking confirmed) ───────
    public bool $booking_confirmed_partner_email;  // Email to partner
    public bool $booking_confirmed_guide_email;    // Email to guide

    // ── Driver Dispatch Assignment ──────────────────────────────────────────
    public bool $driver_assigned_email;       // Email to driver

    // ── Booking Cancellation (partner booking) ──────────────────────────────
    public bool $booking_cancelled_partner_email;    // Email to partner
    public bool $booking_cancelled_transport_email;  // Email to transport company
    public bool $booking_cancelled_driver_email;     // Email to each driver
    public bool $booking_cancelled_guide_email;      // Email to guide
    public bool $booking_cancelled_admin_email;      // Email to admin

    // ── PAX Capacity Alerts ─────────────────────────────────────────────────
    public bool $pax_alert_email;             // Email to admin

    public static function group(): string
    {
        return 'notifications';
    }
}
