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

class TransportPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('transport')
            ->path('transport')
            ->login()
            ->colors([
                'primary' => Color::hex('#d97706'), // Amber — distinct from admin and partner
            ])
            ->brandName('Booklix Transport Portal')
            ->discoverResources(
                in: app_path('Filament/Transport/Resources'),
                for: 'App\\Filament\\Transport\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Transport/Pages'),
                for: 'App\\Filament\\Transport\\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Transport/Widgets'),
                for: 'App\\Filament\\Transport\\Widgets'
            )
            ->widgets([
                AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Fleet Management'),
                NavigationGroup::make('Dispatches'),
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
