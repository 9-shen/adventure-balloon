<?php

namespace App\Filament\Greeter\Widgets;

use App\Models\Booking;
use App\Models\BookingCustomer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GreeterStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $today = today();

        // All active bookings today
        $bookingsBase = Booking::whereDate('flight_date', $today)
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed']);

        $totalBookings = (clone $bookingsBase)->count();

        // PAX counts
        $todayBookingIds = (clone $bookingsBase)->pluck('id');
        $paxBase = BookingCustomer::whereIn('booking_id', $todayBookingIds);

        $totalPax  = (clone $paxBase)->count();
        $showPax   = (clone $paxBase)->where('attendance', 'show')->count();
        $noShowPax = (clone $paxBase)->where('attendance', 'no_show')->count();
        $waitPax   = $totalPax - $showPax - $noShowPax;

        return [
            Stat::make("Today's Flights", $totalBookings)
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Total PAX Today', $totalPax)
                ->description('Expected passengers')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Checked In', $showPax)
                ->description("{$showPax} of {$totalPax} showed")
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
