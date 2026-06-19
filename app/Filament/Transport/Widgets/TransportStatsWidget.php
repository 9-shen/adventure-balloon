<?php

namespace App\Filament\Transport\Widgets;

use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class TransportStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $companyId = $user->transport_company_id;

        $totalDispatches = Dispatch::where('transport_company_id', $companyId)->count();
        
        $deliveredCount = Dispatch::where('transport_company_id', $companyId)
            ->where('status', 'delivered')
            ->count();
            
        $expectedPayment = Dispatch::where('transport_company_id', $companyId)
            ->where('status', 'confirmed')
            ->sum('transport_cost');
            
        $paymentDue = Dispatch::where('transport_company_id', $companyId)
            ->where('status', 'delivered')
            ->whereNull('billed_at')
            ->sum('transport_cost');

        return [
            Stat::make('Total Dispatches', new HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $totalDispatches . '</span>'))
                ->icon('heroicon-o-truck')
                ->description('All time dispatches')
                ->color('primary'),

            Stat::make('Delivered Dispatches', new HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . $deliveredCount . '</span>'))
                ->icon('heroicon-o-check-badge')
                ->description('Successfully completed')
                ->color('success'),

            Stat::make('Expected Payment', new HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . number_format($expectedPayment, 2) . ' ' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . '</span>'))
                ->icon('heroicon-o-clock')
                ->description('From confirmed dispatches')
                ->color('warning'),
                
            Stat::make('Payment Due', new HtmlString('<span style="font-size: 1.25rem; font-weight: 700;">' . number_format($paymentDue, 2) . ' ' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . '</span>'))
                ->icon('heroicon-o-banknotes')
                ->description('From delivered & unbilled dispatches')
                ->color('danger'),
        ];
    }
}
