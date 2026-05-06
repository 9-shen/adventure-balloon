<?php

namespace App\Filament\BalloonDispatcher\Widgets;

use App\Models\BalloonDispatch;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class BalloonDispatcherStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;
    public int|string|array $columnSpan = 'full';

    private function val(int|string $value): HtmlString
    {
        return new HtmlString(
            '<span style="font-size: 1.25rem; font-weight: 700;">' . e($value) . '</span>'
        );
    }

    protected function getStats(): array
    {
        $todayBookings = Booking::whereDate('flight_date', today())
            ->whereIn('booking_status', ['confirmed', 'pending'])
            ->count();

        $tomorrowBookings = Booking::whereDate('flight_date', today()->addDay())
            ->whereIn('booking_status', ['confirmed', 'pending'])
            ->count();

        $totalDispatches = BalloonDispatch::count();
        $thisMonthDispatches = BalloonDispatch::whereMonth('dispatch_date', now()->month)
            ->whereYear('dispatch_date', now()->year)
            ->count();

        return [
            Stat::make("Today's Bookings", $this->val($todayBookings))
                ->description('Confirmed & Pending for today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make("Tomorrow's Bookings", $this->val($tomorrowBookings))
                ->description('Confirmed & Pending for tomorrow')
                ->descriptionIcon('heroicon-o-arrow-right-circle')
                ->color('info'),

            Stat::make('Total Balloon Dispatches', $this->val($totalDispatches))
                ->description('All time')
                ->descriptionIcon('heroicon-o-paper-airplane')
                ->color('gray'),

            Stat::make('Dispatches This Month', $this->val($thisMonthDispatches))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('success'),
        ];
    }
}
