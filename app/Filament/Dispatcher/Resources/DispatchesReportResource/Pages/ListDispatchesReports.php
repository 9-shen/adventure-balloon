<?php

namespace App\Filament\Dispatcher\Resources\DispatchesReportResource\Pages;

use App\Filament\Dispatcher\Resources\DispatchesReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDispatchesReports extends ListRecords
{
    protected static string $resource = DispatchesReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_csv')
                ->label('Export Dispatches Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\DispatchesReportQueryExport($this->getFilteredTableQuery()),
                        'dispatches_reports.csv',
                        \Maatwebsite\Excel\Excel::CSV
                    );
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('All Dispatches'),
            
            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Today')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', today()))
                ->badge(\App\Models\Dispatch::query()->whereDate('flight_date', today())->count()),
                
            'tomorrow' => \Filament\Schemas\Components\Tabs\Tab::make('Tomorrow')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', today()->addDay()))
                ->badge(\App\Models\Dispatch::query()->whereDate('flight_date', today()->addDay())->count()),
                
            'next_7_days' => \Filament\Schemas\Components\Tabs\Tab::make('Next 7 Days')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', '>=', today())->whereDate('flight_date', '<=', today()->addDays(7)))
                ->badge(\App\Models\Dispatch::query()->whereDate('flight_date', '>=', today())->whereDate('flight_date', '<=', today()->addDays(7))->count()),
        ];
    }
}
