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
            Stat::make('Total Revenue', 'MAD ' . number_format($totalRevenue, 2))
                ->description('All active bookings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Collected', 'MAD ' . number_format($collected, 2))
                ->description('Total payments received')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Outstanding', 'MAD ' . number_format($outstanding, 2))
                ->description('Pending balance due')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Total Bookings', $bookingsCount)
                ->description($totalPax . ' total PAX')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}

