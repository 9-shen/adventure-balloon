<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    protected function getStats(): array
    {
        $month = now()->month;
        $year  = now()->year;

        $topProducts = Booking::query()
            ->select('product_id', DB::raw('SUM(final_amount) as revenue'), DB::raw('SUM(adult_pax + child_pax) as total_pax'))
            ->with('product:id,name')
            ->whereYear('flight_date', $year)
            ->whereMonth('flight_date', $month)
            ->whereIn('booking_status', ['confirmed', 'completed'])
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(3)
            ->get();

        $stats = [];
        $icons = [
            'heroicon-o-trophy',
            'heroicon-o-star',
            'heroicon-o-sparkles',
        ];
        $colors = ['success', 'warning', 'info'];

        foreach ($topProducts as $i => $row) {
            $stats[] = Stat::make(
                $row->product?->name ?? 'Unknown Product',
                'MAD ' . number_format((float) $row->revenue, 0)
            )
                ->description($row->total_pax . ' PAX this month')
                ->descriptionIcon($icons[$i] ?? 'heroicon-o-chart-bar')
                ->color($colors[$i] ?? 'gray');
        }

        if (empty($stats)) {
            $stats[] = Stat::make('No Data', 'No bookings this month')
                ->description('No confirmed bookings yet')
                ->color('gray');
        }

        return $stats;
    }
}
