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
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Manager\Widgets\OperationsHealthWidget::class,
            \App\Filament\Manager\Widgets\TopPartnersWidget::class,
            \App\Filament\Manager\Widgets\RevenueChartWidget::class,
        ];
    }
}
