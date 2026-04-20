<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Filament Livewire components
        Livewire::component(
            'filament.livewire.notifications',
            \Filament\Notifications\Livewire\Notifications::class
        );

        // Force HTTPS in production — prevents Filament CSS/JS mixed content errors
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
