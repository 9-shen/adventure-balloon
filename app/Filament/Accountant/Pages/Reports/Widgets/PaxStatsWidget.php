<?php

namespace App\Filament\Accountant\Pages\Reports\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PaxStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $query = Booking::query()
            ->whereIn('booking_status', ['confirmed', 'completed']);

        $flights  = (clone $query)->distinct('flight_date')->count('flight_date');
        $totalPax = (int) (clone $query)->sum(DB::raw('adult_pax + child_pax'));
        $avgPax   = $flights > 0 ? round($totalPax / $flights, 1) : 0;

        $noShowPax             = (int) (clone $query)->where('attendance', 'no_show')->sum(DB::raw('adult_pax + child_pax'));
        $totalAttendanceChecked = (int) (clone $query)->whereNotNull('attendance')->sum(DB::raw('adult_pax + child_pax'));
        $noShowRate            = $totalAttendanceChecked > 0
            ? round(($noShowPax / $totalAttendanceChecked) * 100, 1)
            : 0;

        return [
            Stat::make('Total Flights', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $flights . '</span>'))
                ->description('Unique flight dates')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Total PAX', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $totalPax . '</span>'))
                ->description('All passengers')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Avg PAX / Flight', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $avgPax . '</span>'))
                ->description('Average load per flight')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('No-Show Rate', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $noShowRate . '%</span>'))
                ->description('No-shows vs Total checked')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}

