<?php

namespace App\Filament\Accountant\Pages\Reports\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RevenueStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $query = Booking::query()
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed']);

        $totalRevenue   = (float) (clone $query)->sum('final_amount');
        $collected      = (float) (clone $query)->sum('amount_paid');
        $outstanding    = (float) (clone $query)->sum('balance_due');
        $bookingsCount  = (clone $query)->count();
        $totalPax       = (int) (clone $query)->sum(DB::raw('adult_pax + child_pax'));

        return [
            Stat::make('Total Revenue', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($totalRevenue, 2) . '</span>'))
                ->description('All active bookings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Collected', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($collected, 2) . '</span>'))
                ->description('Total payments received')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Outstanding', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($outstanding, 2) . '</span>'))
                ->description('Pending balance due')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Total Bookings', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $bookingsCount . '</span>'))
                ->description($totalPax . ' total PAX')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}

