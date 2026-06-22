<?php

namespace App\Filament\Sales\Widgets;

use App\Models\Booking;
use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SalesOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    // Responsive: fill full width on all viewports
    public int|string|array $columnSpan = 'full';

    /** Wrap a value in the standard bold/larger font used across all portals. */
    private function val(int|string $value): HtmlString
    {
        return new HtmlString(
            '<span style="font-size: 1.25rem; font-weight: 700;">' . e($value) . '</span>'
        );
    }

    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ── Booking stats (scoped to bookings created by this Sales user) ──────
        $base      = Booking::where('created_by', $user->id);
        $total     = (clone $base)->count();
        $confirmed = (clone $base)->where('booking_status', 'confirmed')->count();
        $pending   = (clone $base)->where('booking_status', 'pending')->count();
        $completed = (clone $base)->where('booking_status', 'completed')->count();

        // ── Dispatch stats ─────────────────────────────────────────────────────
        $salesBookingIds = (clone $base)->pluck('id');

        $dispatchBase       = Dispatch::whereIn('booking_id', $salesBookingIds);
        $totalDispatches    = (clone $dispatchBase)->count();
        $dispatchedConfirmed = (clone $dispatchBase)->where('status', 'confirmed')->count();
        $dispatchedPending   = (clone $dispatchBase)->where('status', 'pending')->count();

        return [
            // ── Role Card ──────────────────────────────────────────────────────
            Stat::make('Role', $this->val('Sales Representative'))
                ->description('Operating under Main Application')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('primary'),

            // ── Booking cards ──────────────────────────────────────────────────
            Stat::make('Total Bookings', $this->val($total))
                ->description('Your created bookings')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('gray'),

            Stat::make('Confirmed', $this->val($confirmed))
                ->description('Ready to fly')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Pending', $this->val($pending))
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Completed', $this->val($completed))
                ->description('Flights done')
                ->descriptionIcon('heroicon-o-flag')
                ->color('info'),

            // ── Dispatch cards ─────────────────────────────────────────────────
            Stat::make('Total Dispatches', $this->val($totalDispatches))
                ->description('Transport for your bookings')
                ->descriptionIcon('heroicon-o-truck')
                ->color('primary'),

            Stat::make('Confirmed Dispatches', $this->val($dispatchedConfirmed))
                ->description('Transport confirmed')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Pending Dispatches', $this->val($dispatchedPending))
                ->description('Awaiting transport confirmation')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),
        ];
    }
}
