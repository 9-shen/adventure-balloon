<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class BookingTypeChartWidget extends ChartWidget
{
    protected array|string|int $columnSpan = 1;
    protected ?string $heading = 'Booking Type Split';
    protected ?string $maxHeight = '220px';
    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    protected function getData(): array
    {
        $base = Booking::whereIn('booking_status', ['confirmed', 'pending', 'completed']);

        $regular = (clone $base)->where('type', 'regular')->count();
        $partner = (clone $base)->where('type', 'partner')->count();

        // Fallback: if 'type' column doesn't filter clearly, use partner_id
        if ($regular === 0 && $partner === 0) {
            $partner = (clone $base)->whereNotNull('partner_id')->count();
            $regular = (clone $base)->whereNull('partner_id')->count();
        }

        $data   = [];
        $labels = [];
        $bgs    = [];

        if ($regular > 0) {
            $data[]   = $regular;
            $labels[] = 'Regular';
            $bgs[]    = '#6366f1'; // indigo
        }

        if ($partner > 0) {
            $data[]   = $partner;
            $labels[] = 'Partner';
            $bgs[]    = '#f59e0b'; // amber
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
