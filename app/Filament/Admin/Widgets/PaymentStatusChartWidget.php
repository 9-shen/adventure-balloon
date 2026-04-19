<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PaymentStatusChartWidget extends ChartWidget
{
    protected array|string|int $columnSpan = 1;
    protected ?string $heading = 'Payment Status Distribution';
    protected ?string $maxHeight = '220px';
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    protected function getData(): array
    {
        $statuses = ['paid', 'partial', 'due', 'on_site'];
        $colors   = [
            'paid'    => '#22c55e',
            'partial' => '#f59e0b',
            'due'     => '#ef4444',
            'on_site' => '#3b82f6',
        ];
        $labels = [];
        $data   = [];
        $bgs    = [];

        foreach ($statuses as $status) {
            $count = Booking::where('payment_status', $status)
                ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
                ->count();
            if ($count > 0) {
                $labels[] = ucfirst(str_replace('_', '-', $status));
                $data[]   = $count;
                $bgs[]    = $colors[$status];
            }
        }

        return [
            'datasets' => [
                [
                    'data'            => $data,
                    'backgroundColor' => $bgs,
                    'borderWidth'     => 2,
                    'borderColor'     => '#fff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '70%',
        ];
    }
}
