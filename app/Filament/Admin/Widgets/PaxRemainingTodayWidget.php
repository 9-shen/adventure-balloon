<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Settings\PaxSettings;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaxRemainingTodayWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    protected function getStats(): array
    {
        $settings = app(PaxSettings::class);
        $capacity = $settings->daily_pax_capacity;

        // All bookings today (regular + partner share the same bookings table)
        $totalBooked = (int) Booking::whereDate('flight_date', today())
            ->whereIn('booking_status', ['confirmed', 'pending'])
            ->sum(DB::raw('adult_pax + child_pax'));

        $remaining   = max(0, $capacity - $totalBooked);
        $usedPercent = $capacity > 0 ? round(($totalBooked / $capacity) * 100) : 0;

        // Color coding based on fill level
        $remainingColor = match(true) {
            $remaining === 0                => 'danger',
            $remaining <= ($capacity * 0.2) => 'warning',
            default                         => 'success',
        };

        $bookedColor = match(true) {
            $usedPercent >= 100 => 'danger',
            $usedPercent >= 80  => 'warning',
            default             => 'info',
        };

        return [
            Stat::make('Daily Capacity', $capacity)
                ->description('Max PAX allowed per day')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('gray'),

            Stat::make('Booked Today', $totalBooked)
                ->description("{$usedPercent}% of daily capacity used")
                ->descriptionIcon('heroicon-o-ticket')
                ->color($bookedColor),

            Stat::make('PAX Remaining Today', $remaining)
                ->description($remaining === 0 ? 'Fully booked — no slots left!' : "{$remaining} of {$capacity} slots available")
                ->descriptionIcon($remaining === 0 ? 'heroicon-o-x-circle' : 'heroicon-o-arrow-trending-down')
                ->color($remainingColor),
        ];
    }
}
