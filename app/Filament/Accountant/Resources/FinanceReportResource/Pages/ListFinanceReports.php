<?php

namespace App\Filament\Accountant\Resources\FinanceReportResource\Pages;

use App\Filament\Accountant\Resources\FinanceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinanceReports extends ListRecords
{
    protected static string $resource = FinanceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_csv')
                ->label('Export Finance Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\FinanceReportQueryExport($this->getFilteredTableQuery()),
                        'finance_reports.csv',
                        \Maatwebsite\Excel\Excel::CSV
                    );
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('All Bookings'),
            
            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Today')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', today()))
                ->badge(\App\Models\Booking::query()->whereDate('flight_date', today())->count()),
                
            'next_7_days' => \Filament\Schemas\Components\Tabs\Tab::make('Next 7 Days')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', '>=', today())->whereDate('flight_date', '<=', today()->addDays(7)))
                ->badge(\App\Models\Booking::query()->whereDate('flight_date', '>=', today())->whereDate('flight_date', '<=', today()->addDays(7))->count()),
                
            'upcoming' => \Filament\Schemas\Components\Tabs\Tab::make('Upcoming')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', '>', today()))
                ->badge(\App\Models\Booking::query()->whereDate('flight_date', '>', today())->count()),
        ];
    }
}

