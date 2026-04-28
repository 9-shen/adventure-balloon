<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ApplyEmailSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class GuidePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('guide')
            ->path('guide')
            ->login()
            ->passwordReset()
            ->colors([
                'primary' => Color::hex('#7c3aed'), // Violet — distinct from other panels
            ])
            ->brandName('Guide Portal')
            ->favicon(asset('images/favicon.png'))
            ->discoverResources(
                in: app_path('Filament/Guide/Resources'),
                for: 'App\\Filament\\Guide\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Guide/Pages'),
                for: 'App\\Filament\\Guide\\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Guide/Widgets'),
                for: 'App\\Filament\\Guide\\Widgets'
            )
            ->widgets([
                AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('My Bookings'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ApplyEmailSettings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
