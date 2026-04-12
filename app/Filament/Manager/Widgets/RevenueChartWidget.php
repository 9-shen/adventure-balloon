<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Revenue Trend (Current Year)';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        $currentYear = Carbon::now()->year;

        for ($month = 1; $month <= 12; $month++) {
            $sum = Booking::query()
                ->where('booking_status', '!=', 'cancelled')
                ->whereYear('flight_date', $currentYear)
                ->whereMonth('flight_date', $month)
                ->sum('final_amount');
            
            $data[] = (float) $sum;
            $labels[] = Carbon::create()->month($month)->shortMonthName;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Bookings Revenue (MAD)',
                    'data' => $data,
                    'borderColor' => '#f59e0b', // amber-500
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
