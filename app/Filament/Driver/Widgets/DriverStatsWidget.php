<?php

namespace App\Filament\Driver\Widgets;

use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\HtmlString;

class DriverStatsWidget extends BaseWidget
{
    // Polling and lazy loading to match Greeter dashboard improvements
    protected ?string $pollingInterval = '30s';
    protected bool $isLazy = false;

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
            Stat::make('Today\'s Dispatches', new HtmlString("<span style='font-size: 1.25rem; font-weight: bold;'>{$todayDispatches}</span>"))
                ->description('Dispatches scheduled for today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
                
            Stat::make('Upcoming Deliveries', new HtmlString("<span style='font-size: 1.25rem; font-weight: bold;'>{$upcomingDispatches}</span>"))
                ->description('Pending and confirmed dispatches')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('Total Completed', new HtmlString("<span style='font-size: 1.25rem; font-weight: bold;'>{$completedDispatches}</span>"))
                ->description('Lifetime delivered dispatches')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
