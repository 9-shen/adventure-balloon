<?php

namespace App\Filament\Partner\Widgets;

use App\Models\Booking;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PartnerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $partnerId = $user->partner_id;

        $totalBookings = Booking::where('partner_id', $partnerId)
            ->where('type', 'partner')
            ->count();

        $upcomingFlights = Booking::where('partner_id', $partnerId)
            ->where('type', 'partner')
            ->where('booking_status', 'confirmed')
            ->whereDate('flight_date', '>=', now())
            ->count();

        $totalBilled = Invoice::where('partner_id', $partnerId)
            ->whereIn('status', ['sent', 'paid'])
            ->sum('total_amount');

        $totalOutstanding = Invoice::where('partner_id', $partnerId)
            ->whereNotIn('status', ['paid'])
            ->sum('total_amount');

        return [
            Stat::make('Total Bookings', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $totalBookings . '</span>'))
                ->description('All bookings under your agency')
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Upcoming Confirmed Flights', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $upcomingFlights . '</span>'))
                ->description('Confirmed flights from today onwards')
                ->icon('heroicon-o-paper-airplane')
                ->color('success'),

            Stat::make('Total Invoiced', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . number_format((float) $totalBilled, 2) . ' MAD</span>'))
                ->description(number_format((float) $totalOutstanding, 2) . ' ' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' outstanding')
                ->icon('heroicon-o-document-text')
                ->color('warning'),
        ];
    }
}
