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
use App\Filament\Sales\Widgets\SalesOverviewWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SalesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sales')
            ->path('sales')
            ->login()
            ->passwordReset()
            
            ->brandName('Sales Portal')
            ->favicon(asset('images/logo.jpg'))
            ->colors([
                'primary' => Color::hex('#db2777'), // Pink/Magenta — distinct from other panels
            ])
            ->discoverResources(
                in: app_path('Filament/Sales/Resources'),
                for: 'App\\Filament\\Sales\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Sales/Pages'),
                for: 'App\\Filament\\Sales\\Pages'
            )
            ->pages([
                Dashboard::class,
                \App\Filament\Sales\Pages\Profile::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Sales/Widgets'),
                for: 'App\\Filament\\Sales\\Widgets'
            )
            ->widgets([
                SalesOverviewWidget::class,
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
