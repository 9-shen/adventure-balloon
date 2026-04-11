<?php

namespace App\Filament\Transport\Widgets;

use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

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
            Stat::make('Total Dispatches', $totalDispatches)
                ->icon('heroicon-o-truck')
                ->description('All time dispatches')
                ->color('primary'),

            Stat::make('Delivered Dispatches', $deliveredCount)
                ->icon('heroicon-o-check-badge')
                ->description('Successfully completed')
                ->color('success'),

            Stat::make('Expected Payment', number_format($expectedPayment, 2) . ' MAD')
                ->icon('heroicon-o-clock')
                ->description('From confirmed dispatches')
                ->color('warning'),
                
            Stat::make('Payment Due', number_format($paymentDue, 2) . ' MAD')
                ->icon('heroicon-o-banknotes')
                ->description('From delivered & unbilled dispatches')
                ->color('danger'),
        ];
    }
}
