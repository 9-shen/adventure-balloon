<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DispatcherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dispatcher')
            ->path('dispatcher')
            ->login()
            
            ->brandName('Dispatcher Portal')
            ->favicon(asset('images/logo.jpg'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/dispatcher/theme.css')
            ->discoverResources(in: app_path('Filament/Dispatcher/Resources'), for: 'App\Filament\Dispatcher\Resources')
            ->discoverPages(in: app_path('Filament/Dispatcher/Pages'), for: 'App\Filament\Dispatcher\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Dispatcher/Widgets'), for: 'App\Filament\Dispatcher\Widgets')
            ->widgets([
                // Remove AccountWidget to hide the default welcome widget
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('My Profile')
                    ->url(fn (): string => \App\Filament\Dispatcher\Pages\Profile::getUrl())
                    ->icon('heroicon-o-user'),
            ])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('Bookings'),
                \Filament\Navigation\NavigationGroup::make('Operations'),
                \Filament\Navigation\NavigationGroup::make('Reports'),
                \Filament\Navigation\NavigationGroup::make('Directory'),
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
                \App\Http\Middleware\ApplyEmailSettings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
