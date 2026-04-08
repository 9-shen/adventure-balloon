<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\BookingCustomer;
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

        // Booking-level counts for today
        $bookingsBase = Booking::whereDate('flight_date', $today)
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed']);

        $totalBookings = (clone $bookingsBase)->count();

        // PAX-level counts from booking_customers joined to today's bookings
        $todayBookingIds = (clone $bookingsBase)->pluck('id');

        $paxBase = BookingCustomer::whereIn('booking_id', $todayBookingIds);

        $totalPax  = (clone $paxBase)->count();
        $showPax   = (clone $paxBase)->where('attendance', 'show')->count();
        $noShowPax = (clone $paxBase)->where('attendance', 'no_show')->count();
        $waitPax   = $totalPax - $showPax - $noShowPax;

        return [
            Stat::make("Today's Flights", $totalBookings)
                ->description('Bookings scheduled for today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Total PAX Today', $totalPax)
                ->description('Individual passengers')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Checked In', $showPax)
                ->description("{$showPax} of {$totalPax} PAX showed")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Awaiting', $waitPax)
                ->description('Attendance not yet marked')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('No-Show', $noShowPax)
                ->description("{$noShowPax} PAX did not appear")
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
