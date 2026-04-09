<?php

namespace App\Filament\Admin\Pages\Reports\Widgets;

use App\Filament\Admin\Pages\Reports\DuePaymentsReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class DuePaymentsStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return DuePaymentsReport::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();
        
        $totalOutstanding = (float) $query->clone()->sum('balance_due');
        $dueCount = $query->clone()->count();
        $highestBalance = (float) $query->clone()->max('balance_due');

        return [
            Stat::make('Total Outstanding', 'MAD ' . number_format($totalOutstanding, 2))
                ->description('Across all active filters')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Due Bookings Count', $dueCount)
                ->description('Bookings with balance > 0')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('warning'),

            Stat::make('Highest Single Balance', 'MAD ' . number_format($highestBalance, 2))
                ->description('Max amount owed')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),
        ];
    }
}
