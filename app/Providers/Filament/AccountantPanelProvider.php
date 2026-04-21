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

class AccountantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('accountant')
            ->path('accountant')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('Booklix Finance Portal')
            ->viteTheme('resources/css/filament/accountant/theme.css')
            ->discoverResources(
                in: app_path('Filament/Accountant/Resources'),
                for: 'App\\Filament\\Accountant\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Accountant/Pages'),
                for: 'App\\Filament\\Accountant\\Pages'
            )
            ->pages([
                Dashboard::class,
                \App\Filament\Admin\Pages\BookingCalendarPage::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Accountant/Widgets'),
                for: 'App\\Filament\\Accountant\\Widgets'
            )
            ->widgets([
                AccountWidget::class,
                \App\Filament\Accountant\Widgets\CashFlowOverview::class,
                \App\Filament\Accountant\Widgets\RecentInvoicesWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Bookings'),
                NavigationGroup::make('Accountant Module'),
                NavigationGroup::make('Invoicing'),
                NavigationGroup::make('Transport Finance'),
                NavigationGroup::make('Financial Reports'),
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
