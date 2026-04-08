<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GreeterTodayStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'greeter']) ?? false;
    }

    protected function getStats(): array
    {
        $today = today();

        $base = Booking::whereDate('flight_date', $today)
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed']);

        $total   = (clone $base)->count();
        $show    = (clone $base)->where('attendance', 'show')->count();
        $noShow  = (clone $base)->where('attendance', 'no_show')->count();
        $pending = $total - $show - $noShow;

        $totalPax = (clone $base)->sum(DB::raw('adult_pax + child_pax'));

        return [
            Stat::make("Today's Bookings", $total)
                ->description('Total flights scheduled for today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Total PAX Today', $totalPax)
                ->description('Passengers across all bookings')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Checked In', $show)
                ->description('Marked as Show')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Awaiting', $pending)
                ->description('Attendance not yet marked')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('No-Show', $noShow)
                ->description('Did not appear')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
