<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\GreeterTodayStatsWidget;
use App\Filament\Admin\Widgets\PaxAlertWidget;
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
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#e71a39'),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->resources([
                \App\Filament\Accountant\Resources\AccountantBookingResource::class,
                \App\Filament\Accountant\Resources\InvoiceResource::class,
                \App\Filament\Accountant\Resources\PartnerInvoiceResource::class,
                \App\Filament\Accountant\Resources\TransportBillResource::class,
                \App\Filament\Accountant\Resources\TransporterBillingResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
                \App\Filament\Accountant\Pages\Reports\RevenueReport::class,
                \App\Filament\Accountant\Pages\Reports\DuePaymentsReport::class,
                \App\Filament\Accountant\Pages\Reports\PartnerSummaryReport::class,
                \App\Filament\Accountant\Pages\Reports\PaxStatsReport::class,
                \App\Filament\Accountant\Pages\Reports\TransportCostReport::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                PaxAlertWidget::class,
                GreeterTodayStatsWidget::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Bookings'),
                NavigationGroup::make('Greeter'),
                NavigationGroup::make('Accountant Module'),
                NavigationGroup::make('Invoicing'),
                NavigationGroup::make('Transport Finance'),
                NavigationGroup::make('Financial Reports'),
                NavigationGroup::make('Transport Management'),
                NavigationGroup::make('Partner Management'),
                NavigationGroup::make('Product Management'),
                NavigationGroup::make('User Management'),
                NavigationGroup::make('Settings')
                    ->collapsed(),
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
