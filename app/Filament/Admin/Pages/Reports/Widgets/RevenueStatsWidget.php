<?php

namespace App\Filament\Admin\Pages\Reports\Widgets;

use App\Filament\Admin\Pages\Reports\RevenueReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class RevenueStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return RevenueReport::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();
        
        $totalRevenue = (float) $query->clone()->sum('final_amount');
        $collected = (float) $query->clone()->sum('amount_paid');
        $outstanding = (float) $query->clone()->sum('balance_due');
        $bookingsCount = $query->clone()->count();

        // Calculate total PAX
        $totalPax = (int) $query->clone()->sum(DB::raw('adult_pax + child_pax'));

        return [
            Stat::make('Total Revenue', 'MAD ' . number_format($totalRevenue, 2))
                ->description('All filtered bookings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Collected', 'MAD ' . number_format($collected, 2))
                ->description('Total payments received')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Outstanding', 'MAD ' . number_format($outstanding, 2))
                ->description('Pending balance due')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Total Bookings', $bookingsCount)
                ->description($totalPax . ' total PAX')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
