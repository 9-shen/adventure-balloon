<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OperationsHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. High Capacity Flights
        // Let's count how many upcoming flight dates have more than 200 PAX.
        $highCapacityDays = Booking::query()
            ->where('flight_date', '>=', Carbon::today())
            ->whereIn('booking_status', ['confirmed', 'pending'])
            ->selectRaw('flight_date, SUM(adult_pax + child_pax) as total_pax')
            ->groupBy('flight_date')
            ->having('total_pax', '>=', 200)
            ->count();

        // 2. Confirmed Bookings with No Dispatch
        // Tomorrow onwards
        $noDispatchBookings = Booking::query()
            ->where('flight_date', '>=', Carbon::today())
            ->where('booking_status', 'confirmed')
            ->doesntHave('dispatch')
            ->count();

        // 3. Delayed Payments (Confirmed but unpaid and not on_site)
        $delayedPayments = Booking::query()
            ->where('booking_status', 'confirmed')
            ->whereIn('payment_status', ['due', 'partial'])
            ->count();

        return [
            Stat::make('High Capacity Flights', $highCapacityDays)
                ->description('Upcoming days > 200 PAX')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($highCapacityDays > 0 ? 'warning' : 'success'),
            
            Stat::make('Pending Dispatches', $noDispatchBookings)
                ->description('Confirmed bookings needing dispatch')
                ->descriptionIcon('heroicon-m-truck')
                ->color($noDispatchBookings > 0 ? 'danger' : 'success'),

            Stat::make('Delayed Payments', $delayedPayments)
                ->description('Confirmed bookings w/ unpaid balance')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($delayedPayments > 0 ? 'danger' : 'success'),
        ];
    }
}
