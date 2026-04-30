<?php

namespace App\Filament\Greeter\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class GreeterStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = today();

        // ── Booking-level counts ───────────────────────────────────────────────
        $bookingsBase = Booking::whereDate('flight_date', $today)
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
            ->withoutGlobalScopes();

        $totalBookings = (clone $bookingsBase)->count();

        // ── Real total PAX = sum of adult_pax + child_pax on bookings ─────────
        // This is the correct denominator — NOT the number of customer rows filed
        $totalPax = (int) (clone $bookingsBase)
            ->sum(DB::raw('adult_pax + child_pax'));

        // ── Showed PAX = attended_pax override if set, else per-row show count ─
        // We load lightweight data to respect the override per booking
        $bookings = (clone $bookingsBase)
            ->with('customers:id,booking_id,attendance')
            ->get(['id', 'adult_pax', 'child_pax', 'attended_pax']);

        $showedPax  = $bookings->sum(fn (Booking $b): int => $b->getShowedPax());
        $noShowPax  = $bookings->sum(fn (Booking $b): int =>
            $b->customers->where('attendance', 'no_show')->count()
        );
        $waitingPax = max(0, $totalPax - $showedPax - $noShowPax);

        // Helper: styled value
        $val = fn (int $n): HtmlString => new HtmlString(
            '<span style="font-size: 1.25rem; font-weight: 700;">' . $n . '</span>'
        );

        return [
            Stat::make("Today's Flights", $val($totalBookings))
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Total PAX Today', $val($totalPax))
                ->description('Expected passengers')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Checked In', $val($showedPax))
                ->description("{$showedPax} of {$totalPax} showed")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Awaiting', $val($waitingPax))
                ->description('Attendance not yet marked')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('No-Show', $val($noShowPax))
                ->description("{$noShowPax} PAX did not appear")
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
