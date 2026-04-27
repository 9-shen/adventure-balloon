<?php

namespace App\Filament\Manager\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use BackedEnum;

class ManagerDashboard extends BaseDashboard
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = -2;

    public function getTitle(): string | Htmlable
    {
        return 'Operations Dashboard';
    }

    public function getColumns(): int | array
    {
        return [
            'default' => 1,   // mobile: 1 col
            'sm'      => 2,   // tablet: 2 col
            'lg'      => 3,   // desktop: 3 col
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Manager\Widgets\OperationsHealthWidget::class,
            \App\Filament\Manager\Widgets\TodayBookingsWidget::class,
            \App\Filament\Manager\Widgets\BookingActivityChartWidget::class,
        ];
    }
}
