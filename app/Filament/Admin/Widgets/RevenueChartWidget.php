<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int    $sort        = 5;
    protected string  $color       = 'primary';
    protected array|string|int $columnSpan = 'full';
    protected ?string $heading = 'Monthly Revenue (Current Year)';

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant', 'manager']) ?? false;
    }

    protected function getData(): array
    {
        $year   = now()->year;
        $labels = [];
        $data   = [];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = date('M', mktime(0, 0, 0, $m, 1));
            $data[]   = (float) Booking::query()
                ->whereYear('flight_date', $year)
                ->whereMonth('flight_date', $m)
                ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
                ->sum('final_amount');
        }

        return [
            'datasets' => [
                [
                    'label'                     => 'Revenue (MAD)',
                    'data'                      => $data,
                    'borderColor'               => '#e71a39',
                    'backgroundColor'           => 'rgba(231, 26, 57, 0.08)',
                    'fill'                      => true,
                    'tension'                   => 0.4,
                    'pointBackgroundColor'       => '#e71a39',
                    'pointBorderColor'           => '#fff',
                    'pointHoverBackgroundColor'  => '#fff',
                    'pointHoverBorderColor'      => '#e71a39',
                    'pointRadius'               => 5,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'callback' => 'function(value) { return "MAD " + value.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}
