<?php

namespace App\Providers;

use App\Auth\Http\Responses\LogoutResponse;
use App\Support\MailConfig;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Redirect all Filament panel logouts to the home page
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        // Warn in logs if debug is on in production
        if (app()->isProduction() && config('app.debug')) {
            \Illuminate\Support\Facades\Log::critical('SECURITY: APP_DEBUG=true detected in production!');
        }

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

        // Dynamically override Filament default currency
        try {
            $currency = app(\App\Settings\AppSettings::class)->getIsoCurrency();
        } catch (\Throwable $e) {
            $currency = 'MAD';
        }

        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table) use ($currency): void {
            $table->defaultCurrency($currency);
        });

        \Filament\Schemas\Schema::configureUsing(function (\Filament\Schemas\Schema $schema) use ($currency): void {
            $schema->defaultCurrency($currency);
        });
    }
}

