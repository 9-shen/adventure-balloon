<?php

namespace App\Filament\Admin\Pages\Reports\Widgets;

use App\Filament\Admin\Pages\Reports\PaxStatsReport;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class PaxStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return PaxStatsReport::class;
    }

    protected function getStats(): array
    {
        // For stats, we want raw aggregations, but we must apply the same date range filters.
        // It's safer to re-create the base query with the table filters than to use the grouped query.
        $filters = $this->getTableFiltersForm()->getState();
        
        $base = Booking::query()
            ->whereIn('booking_status', ['confirmed', 'completed'])
            ->when($filters['date_range']['date_from'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '>=', $v))
            ->when($filters['date_range']['date_until'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '<=', $v))
            ->when($filters['type']['value'] ?? null, fn($q, $v) => $q->where('type', $v));

        $flights = $base->clone()->distinct('flight_date')->count('flight_date');
        $totalPax = (int) $base->clone()->sum(DB::raw('adult_pax + child_pax'));
        
        $avgPax = $flights > 0 ? round($totalPax / $flights, 1) : 0;
        
        $noShowPax = (int) $base->clone()->where('attendance', 'no_show')->sum(DB::raw('adult_pax + child_pax'));
        $totalAttendanceAssigned = (int) $base->clone()->whereNotNull('attendance')->sum(DB::raw('adult_pax + child_pax'));
        
        $noShowRate = $totalAttendanceAssigned > 0 
            ? round(($noShowPax / $totalAttendanceAssigned) * 100, 1) 
            : 0;

        return [
            Stat::make('Total Flights', $flights)
                ->description('Unique flight dates')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Total PAX', $totalPax)
                ->description('All passengers')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Avg PAX / Flight', $avgPax)
                ->description('Average load')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
                
            Stat::make('No-Show Rate', $noShowRate . '%')
                ->description('No-shows vs Total checked')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
