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
use App\Filament\Guide\Widgets\GuideOverviewWidget;
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
            
            ->brandName('Guide Portal')
            ->favicon(asset('images/logo.jpg'))
            ->colors([
                'primary' => Color::hex('#7c3aed'), // Violet — distinct from other panels
            ])
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
                \App\Filament\Guide\Pages\Profile::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Guide/Widgets'),
                for: 'App\\Filament\\Guide\\Widgets'
            )
            ->widgets([
                GuideOverviewWidget::class,
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
