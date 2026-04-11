<?php

namespace App\Providers\Filament;

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
use App\Filament\Greeter\Widgets\GreeterStatsWidget;

class GreeterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('greeter')
            ->path('greeter')
            ->login()
            ->colors([
                'primary' => Color::hex('#059669'), // Emerald green — field-ops feel
            ])
            ->brandName('Booklix Greeter Portal')
            ->discoverResources(
                in: app_path('Filament/Greeter/Resources'),
                for: 'App\\Filament\\Greeter\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Greeter/Pages'),
                for: 'App\\Filament\\Greeter\\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Greeter/Widgets'),
                for: 'App\\Filament\\Greeter\\Widgets'
            )
            ->widgets([
                AccountWidget::class,
                GreeterStatsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Attendance'),
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
                // NOTE: ApplyEmailSettings intentionally removed — greeter panel
                // doesn't send emails, so we skip the extra DB query on every request.
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
