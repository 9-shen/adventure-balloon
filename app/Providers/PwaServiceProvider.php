<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class PwaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Injects PWA manifest, meta tags, service worker registration,
     * and install prompt into ALL Filament panels via render hooks.
     */
    public function boot(): void
    {
        // Inject PWA meta tags into <head> of every Filament panel
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): HtmlString => new HtmlString($this->getPwaHeadTags()),
        );

        // Inject service worker registration + install prompt at end of <body>
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): HtmlString => new HtmlString($this->getPwaBodyScripts()),
        );
    }

    /**
     * PWA meta tags for the <head> section.
     */
    private function getPwaHeadTags(): string
    {
        return <<<'HTML'
        <!-- PWA Meta Tags -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#e71a39">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Booklix">
        <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
        <meta name="msapplication-TileImage" content="/images/icons/icon-192x192.png">
        <meta name="msapplication-TileColor" content="#e71a39">
        HTML;
    }

    /**
     * Service worker registration script + install prompt component for end of <body>.
     */
    private function getPwaBodyScripts(): string
    {
        $installPrompt = view('components.pwa-install-prompt')->render();

        return <<<HTML
        <!-- Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(registration) {
                            console.log('[PWA] Service Worker registered:', registration.scope);
                        })
                        .catch(function(error) {
                            console.log('[PWA] Service Worker registration failed:', error);
                        });
                });
            }
        </script>
        {$installPrompt}
        HTML;
    }
}
