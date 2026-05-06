<?php

namespace App\Filament\BalloonDispatcher\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class BalloonDispatchPaxTodayWidget extends BaseWidget
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
        $base = Booking::whereDate('flight_date', today())
            ->whereIn('booking_status', ['confirmed', 'pending']);

        $totalPax   = (clone $base)->selectRaw('COALESCE(SUM(adult_pax + child_pax), 0) as total')->value('total') ?? 0;
        $regularPax = (clone $base)->where('type', 'regular')->selectRaw('COALESCE(SUM(adult_pax + child_pax), 0) as total')->value('total') ?? 0;
        $partnerPax = (clone $base)->where('type', 'partner')->selectRaw('COALESCE(SUM(adult_pax + child_pax), 0) as total')->value('total') ?? 0;

        $totalBookings   = (clone $base)->count();
        $regularBookings = (clone $base)->where('type', 'regular')->count();
        $partnerBookings = (clone $base)->where('type', 'partner')->count();

        return [
            Stat::make("Today's Total PAX", $this->val($totalPax))
                ->description("{$totalBookings} booking(s) today")
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Regular PAX Today', $this->val($regularPax))
                ->description("{$regularBookings} regular booking(s)")
                ->descriptionIcon('heroicon-o-user-group')
                ->color('gray'),

            Stat::make('Partner PAX Today', $this->val($partnerPax))
                ->description("{$partnerBookings} partner booking(s)")
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),
        ];
    }
}
