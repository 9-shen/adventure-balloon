<?php

namespace App\Filament\Partner\Widgets;

use App\Models\Booking;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $partnerId = $user->partner_id;

        $bookingQuery = Booking::where('partner_id', $partnerId)->where('type', 'partner');

        $totalBookings = (clone $bookingQuery)->count();
        $confirmedBookings = (clone $bookingQuery)->where('booking_status', 'confirmed')->count();
        $totalPax = (int) (clone $bookingQuery)->sum(DB::raw('adult_pax + child_pax'));

        $invoiceQuery = Invoice::where('partner_id', $partnerId);

        $totalBilled = (float) (clone $invoiceQuery)->whereNotIn('status', ['draft'])->sum('total_amount');
        $totalPaid   = (float) (clone $invoiceQuery)->where('status', 'paid')->sum('total_amount');
        $totalDue    = (float) (clone $invoiceQuery)->whereIn('status', ['sent', 'overdue'])->sum('total_amount');
        $overdue     = (float) (clone $invoiceQuery)->where('status', 'overdue')->sum('total_amount');

        return [
            Stat::make('Total Bookings', $totalBookings)
                ->description("{$confirmedBookings} confirmed · {$totalPax} PAX")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Total Billed', 'MAD ' . number_format($totalBilled, 2))
                ->description('Across all sent & paid invoices')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Paid', 'MAD ' . number_format($totalPaid, 2))
                ->description('Settled invoices')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Outstanding Due', 'MAD ' . number_format($totalDue, 2))
                ->description($overdue > 0
                    ? 'MAD ' . number_format($overdue, 2) . ' overdue ⚠'
                    : 'No overdue invoices'
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdue > 0 ? 'danger' : 'warning'),
        ];
    }
}
