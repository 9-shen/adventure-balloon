<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AccountantTotalRevenueWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'accountant']) ?? false;
    }

    protected function getStats(): array
    {
        $totalPaid = Booking::sum('amount_paid');
        $outstanding = Booking::sum('balance_due');
        $pendingPaymentsCount = Booking::where('balance_due', '>', 0)->count();

        return [
            Stat::make('Total Collected Revenue', number_format($totalPaid, 2) . ' MAD')
                ->description('All-time payments collected')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Total Outstanding Balance', number_format($outstanding, 2) . ' MAD')
                ->description('Total unpaid across all bookings')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
                
            Stat::make('Pending Invoices', $pendingPaymentsCount)
                ->description('Bookings with balance due > 0')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}
