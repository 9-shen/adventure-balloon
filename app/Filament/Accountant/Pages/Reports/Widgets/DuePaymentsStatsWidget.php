<?php

namespace App\Filament\Accountant\Pages\Reports\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DuePaymentsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $query = Booking::query()
            ->where('balance_due', '>', 0)
            ->whereIn('booking_status', ['confirmed', 'pending']);

        $totalOutstanding = (float) (clone $query)->sum('balance_due');
        $dueCount         = (clone $query)->count();
        $highestBalance   = (float) (clone $query)->max('balance_due');

        return [
            Stat::make('Total Outstanding', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($totalOutstanding, 2) . '</span>'))
                ->description('Across all due bookings')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Due Bookings Count', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $dueCount . '</span>'))
                ->description('Bookings with balance > 0')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('warning'),

            Stat::make('Highest Single Balance', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($highestBalance, 2) . '</span>'))
                ->description('Max amount owed')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),
        ];
    }
}

