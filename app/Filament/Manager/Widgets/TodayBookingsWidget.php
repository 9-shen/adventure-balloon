<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $total = Booking::query()
            ->whereDate('flight_date', today())
            ->count();

        $confirmed = Booking::query()
            ->whereDate('flight_date', today())
            ->where('booking_status', 'confirmed')
            ->count();

        $cancelled = Booking::query()
            ->whereDate('flight_date', today())
            ->where('booking_status', 'cancelled')
            ->count();

        $totalPax = Booking::query()
            ->whereDate('flight_date', today())
            ->whereIn('booking_status', ['confirmed', 'pending'])
            ->selectRaw('SUM(adult_pax + child_pax) as pax')
            ->value('pax') ?? 0;

        return [
            Stat::make("Today's Total Bookings", $total)
                ->description('All bookings for today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Confirmed Today', $confirmed)
                ->description("{$totalPax} PAX expected")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($confirmed > 0 ? 'success' : 'gray'),

            Stat::make('Cancelled Today', $cancelled)
                ->description($cancelled > 0 ? 'Cancellations on today\'s date' : 'No cancellations today')
                ->descriptionIcon($cancelled > 0 ? 'heroicon-o-x-circle' : 'heroicon-o-check-badge')
                ->color($cancelled > 0 ? 'danger' : 'success'),
        ];
    }
}
