<?php

namespace App\Filament\Admin\Widgets;

use App\Settings\PaxSettings;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PaxAlertWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.pax-alert-widget';

    // Refresh every 60 seconds
    protected static ?string $pollingInterval = '60s';

    // Show at top of dashboard
    protected static ?int $sort = -2;

    protected int $columns = 1;

    public function getViewData(): array
    {
        $settings   = app(PaxSettings::class);
        $capacity   = $settings->daily_pax_capacity;
        $threshold  = $settings->warning_threshold;

        $bookedToday = DB::table('bookings')
            ->whereDate('flight_date', today())
            ->whereIn('booking_status', ['confirmed', 'pending'])
            ->sum(DB::raw('adult_pax + child_pax'));

        $remaining = $capacity - $bookedToday;

        $status = match(true) {
            $remaining <= 0            => 'full',
            $remaining <= $threshold   => 'warning',
            default                    => 'ok',
        };

        return [
            'status'      => $status,
            'remaining'   => max(0, $remaining),
            'capacity'    => $capacity,
            'bookedToday' => $bookedToday,
            'threshold'   => $threshold,
        ];
    }

    // Only show the widget when there's a warning or the bookings table exists
    public static function canView(): bool
    {
        try {
            $settings  = app(PaxSettings::class);
            $capacity  = $settings->daily_pax_capacity;
            $threshold = $settings->warning_threshold;

            $booked = DB::table('bookings')
                ->whereDate('flight_date', today())
                ->whereIn('booking_status', ['confirmed', 'pending'])
                ->sum(DB::raw('adult_pax + child_pax'));

            $remaining = $capacity - $booked;

            return $remaining <= $threshold;
        } catch (\Exception) {
            // bookings table doesn't exist yet (before Phase 7)
            return false;
        }
    }
}
