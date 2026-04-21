<?php

namespace App\Support;

use App\Settings\EmailSettings;
use Illuminate\Support\Facades\Mail;

class MailConfig
{
    /**
     * Load EmailSettings from the database and apply them to Laravel's
     * runtime mail config. Safe to call in any context (HTTP or queue worker).
     *
     * Symfony Mailer (used internally by Laravel) controls SSL/TLS via the
     * 'scheme' key, not 'encryption':
     *   ssl  (port 465) → scheme = 'smtps'   (full SSL wrapping)
     *   tls  (port 587) → scheme = null       (STARTTLS is the default)
     *
     * IMPORTANT: After updating the config we call Mail::forgetMailers() to
     * purge any already-resolved MailManager singleton. Without this, the SMTP
     * transport that was built from the old .env values keeps being reused even
     * after config() is updated — config() only mutates the in-memory array,
     * it does NOT rebuild already-constructed transport instances.
     */
    public static function applyFromDatabase(): void
    {
        try {
            $settings = app(EmailSettings::class);

            if (empty($settings->host)) {
                return;
            }

            $scheme = match ($settings->encryption) {
                'ssl'   => 'smtps',
                default => null,
            };

            config([
                'mail.default'               => 'smtp',
                'mail.mailers.smtp.scheme'   => $scheme,
                'mail.mailers.smtp.host'     => $settings->host,
                'mail.mailers.smtp.port'     => $settings->port,
                'mail.mailers.smtp.username' => $settings->username,
                'mail.mailers.smtp.password' => $settings->password,
                'mail.from.address'          => $settings->from_address,
                'mail.from.name'             => $settings->from_name,
            ]);

            // Purge resolved mailer instances so the next send creates a fresh
            // SMTP transport using the config values we just applied above.
            Mail::forgetMailers();

        } catch (\Exception) {
            // Settings table may not exist yet (e.g. during migrations) — fail silently.
        }
    }
}
