<?php

namespace App\Providers;

use App\Support\MailConfig;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // The queue worker is a separate PHP process — it never runs HTTP middleware,
        // so ApplyEmailSettings is never called there. This hook fires inside the
        // worker before every job, ensuring DB email settings (Zoho SMTP) are always
        // applied regardless of whether the mail is sent from a web request or a job.
        Queue::before(function () {
            MailConfig::applyFromDatabase();
        });
    }
}

