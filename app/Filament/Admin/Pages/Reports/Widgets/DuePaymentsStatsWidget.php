<?php

namespace App\Filament\Admin\Pages\Reports\Widgets;

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
            Stat::make('Total Outstanding', 'MAD ' . number_format($totalOutstanding, 2))
                ->description('Across all due bookings')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Due Bookings Count', $dueCount)
                ->description('Bookings with balance > 0')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('warning'),

            Stat::make('Highest Single Balance', 'MAD ' . number_format($highestBalance, 2))
                ->description('Max amount owed')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),
        ];
    }
}
