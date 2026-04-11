<?php

namespace App\Filament\Driver\Widgets;

use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DriverStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user || !$user->driver_id) {
            return [];
        }

        $todayDispatches = Dispatch::whereHas('drivers', function ($query) use ($user) {
                $query->where('drivers.id', $user->driver_id);
            })
            ->whereDate('flight_date', today())
            ->count();

        $completedDispatches = Dispatch::whereHas('drivers', function ($query) use ($user) {
                $query->where('drivers.id', $user->driver_id);
            })
            ->where('status', 'delivered')
            ->count();
            
        $upcomingDispatches = Dispatch::whereHas('drivers', function ($query) use ($user) {
                $query->where('drivers.id', $user->driver_id);
            })
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->whereDate('flight_date', '>=', today())
            ->count();

        return [
            Stat::make('Today\'s Dispatches', $todayDispatches)
                ->description('Dispatches scheduled for today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('Upcoming Deliveries', $upcomingDispatches)
                ->description('Pending and confirmed dispatches')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Total Completed', $completedDispatches)
                ->description('Lifetime delivered dispatches')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
