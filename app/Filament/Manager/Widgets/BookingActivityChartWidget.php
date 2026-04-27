<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class BookingActivityChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Booking Activity — Last 14 Days';

    protected ?string $pollingInterval = '120s';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels      = [];
        $confirmed   = [];
        $cancelled   = [];
        $pending     = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d M');

            $base = Booking::query()->whereDate('flight_date', $date);

            $confirmed[] = (clone $base)->where('booking_status', 'confirmed')->count();
            $cancelled[] = (clone $base)->where('booking_status', 'cancelled')->count();
            $pending[]   = (clone $base)->where('booking_status', 'pending')->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Confirmed',
                    'data'            => $confirmed,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',   // green-500
                    'borderColor'     => 'rgba(34, 197, 94, 1)',
                    'borderWidth'     => 1,
                ],
                [
                    'label'           => 'Pending',
                    'data'            => $pending,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.8)',  // amber-500
                    'borderColor'     => 'rgba(245, 158, 11, 1)',
                    'borderWidth'     => 1,
                ],
                [
                    'label'           => 'Cancelled',
                    'data'            => $cancelled,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',   // red-500
                    'borderColor'     => 'rgba(239, 68, 68, 1)',
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
