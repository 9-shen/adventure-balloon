<?php

namespace App\Filament\Accountant\Pages\Reports\Widgets;

use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransportCostStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $query = Dispatch::query();

        $totalCost   = (float) $query->sum('transport_cost');
        $billedCost  = (float) $query->whereNotNull('billed_at')->sum('transport_cost');
        $unbilledCost = $totalCost - $billedCost;
        $totalDispatches = $query->count();

        return [
            Stat::make('Total Dispatches', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . number_format($totalDispatches) . '</span>'))
                ->description('All dispatches')
                ->icon('heroicon-o-truck')
                ->color('info'),

            Stat::make('Total Transport Cost', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($totalCost, 2) . '</span>'))
                ->description('Sum of all dispatch costs')
                ->icon('heroicon-o-calculator')
                ->color('primary'),

            Stat::make('Billed', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($billedCost, 2) . '</span>'))
                ->description('Included in transport bills')
                ->icon('heroicon-o-document-check')
                ->color('success'),

            Stat::make('Unbilled', new \Illuminate\Support\HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ' ' . number_format($unbilledCost, 2) . '</span>'))
                ->description('Not yet billed')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($unbilledCost > 0 ? 'danger' : 'success'),
        ];
    }
}

