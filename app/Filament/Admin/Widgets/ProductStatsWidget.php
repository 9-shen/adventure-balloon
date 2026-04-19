<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductStatsWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    // Cycle through these colors for each product card
    private const COLORS = ['success', 'warning', 'info', 'primary', 'danger', 'gray'];

    // Cycle through icons for variety
    private const ICONS = [
        'heroicon-o-trophy',
        'heroicon-o-star',
        'heroicon-o-sparkles',
        'heroicon-o-fire',
        'heroicon-o-bolt',
        'heroicon-o-gift',
    ];

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

        // Load all active products ordered by sort_order / name
        $products = Product::where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            return [
                Stat::make('No Products', 'No active products found')
                    ->description('Add products in the Products section')
                    ->color('gray'),
            ];
        }

        // Fetch this-month booking aggregates for ALL products in one query
        $rows = Booking::query()
            ->select(
                'product_id',
                DB::raw('COALESCE(SUM(final_amount), 0) as revenue'),
                DB::raw('COALESCE(SUM(adult_pax + child_pax), 0) as total_pax'),
                DB::raw('COUNT(*) as booking_count')
            )
            ->whereYear('flight_date', $year)
            ->whereMonth('flight_date', $month)
            ->whereIn('booking_status', ['confirmed', 'completed', 'pending'])
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $stats = [];

        foreach ($products as $i => $product) {
            $row          = $rows->get($product->id);
            $revenue      = $row ? (float) $row->revenue : 0;
            $pax          = $row ? (int) $row->total_pax : 0;
            $bookingCount = $row ? (int) $row->booking_count : 0;

            $color = self::COLORS[$i % count(self::COLORS)];
            $icon  = self::ICONS[$i % count(self::ICONS)];

            $stats[] = Stat::make(
                $product->name,
                $revenue > 0 ? 'MAD ' . number_format($revenue, 0) : 'No Revenue'
            )
                ->description(
                    $pax > 0
                        ? "{$pax} PAX · {$bookingCount} bookings this month"
                        : 'No bookings this month'
                )
                ->descriptionIcon($icon)
                ->color($color);
        }

        return $stats;
    }
}
