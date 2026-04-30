<?php

namespace App\Filament\Accountant\Widgets;

use App\Models\Booking;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class CashFlowOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Daily Revenue (from paid bookings and invoices created/paid today)
        $today = Carbon::today();
        
        $dailyBookings = Booking::whereDate('created_at', $today)
            ->whereIn('payment_status', ['paid', 'partial', 'on_site'])
            ->sum('amount_paid');
            
        $weeklyBookings = Booking::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->whereIn('payment_status', ['paid', 'partial', 'on_site'])
            ->sum('amount_paid');

        $outstandingBalances = Booking::where('balance_due', '>', 0)->sum('balance_due');
        $unpaidInvoices = Invoice::whereNotIn('status', ['paid'])->count();

        return [
            Stat::make('Today\'s Revenue', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . number_format($dailyBookings, 2) . ' MAD</span>'))
                ->description('Collected today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Weekly Revenue', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . number_format($weeklyBookings, 2) . ' MAD</span>'))
                ->description('Collected this week')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Outstanding Balances', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . number_format($outstandingBalances, 2) . ' MAD</span>'))
                ->description('From unpaid bookings')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
                
            Stat::make('Unpaid Invoices', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $unpaidInvoices . '</span>'))
                ->description('Draft, sent, or overdue')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('danger'),
        ];
    }
}
