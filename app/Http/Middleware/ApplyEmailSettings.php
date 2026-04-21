<?php

namespace App\Http\Middleware;

use App\Settings\EmailSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyEmailSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $settings = app(EmailSettings::class);

            if (! empty($settings->host)) {
                // Laravel's Symfony Mailer uses 'scheme' to control SSL/TLS transport.
                // 'ssl' (port 465) → scheme = 'smtps'
                // 'tls' (port 587) → scheme = null  (STARTTLS is the default)
                // 'none'           → scheme = null
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
            }
        } catch (\Exception) {
            // Settings table may not exist yet during migrations — fail silently
        }

        return $next($request);
    }
}
