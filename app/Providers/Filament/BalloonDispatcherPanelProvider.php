<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ApplyEmailSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BalloonDispatcherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('balloon-dispatcher')
            ->path('balloon-dispatcher')
            ->login()
            ->passwordReset()

            ->brandName('Balloon Dispatcher')
            ->favicon(asset('images/logo.jpg'))
            ->colors([
                'primary' => Color::Sky,
            ])
            ->viteTheme('resources/css/filament/balloon-dispatcher/theme.css')
            ->discoverResources(
                in: app_path('Filament/BalloonDispatcher/Resources'),
                for: 'App\\Filament\\BalloonDispatcher\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/BalloonDispatcher/Pages'),
                for: 'App\\Filament\\BalloonDispatcher\\Pages'
            )
            ->pages([
                Dashboard::class,
                \App\Filament\BalloonDispatcher\Pages\Profile::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/BalloonDispatcher/Widgets'),
                for: 'App\\Filament\\BalloonDispatcher\\Widgets'
            )
            ->widgets([
                \App\Filament\BalloonDispatcher\Widgets\BalloonDispatcherStatsWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('My Profile')
                    ->url(fn (): string => \App\Filament\BalloonDispatcher\Pages\Profile::getUrl())
                    ->icon('heroicon-o-user'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Bookings'),
                NavigationGroup::make('Balloon Dispatch'),
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
