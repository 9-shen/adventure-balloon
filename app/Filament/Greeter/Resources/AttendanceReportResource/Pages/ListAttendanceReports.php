<?php

namespace App\Filament\Greeter\Resources\AttendanceReportResource\Pages;

use App\Filament\Greeter\Resources\AttendanceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceReports extends ListRecords
{
    protected static string $resource = AttendanceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_csv')
                ->label('Export Attendance Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\AttendanceReportQueryExport($this->getFilteredTableQuery()),
                        'attendance_reports.csv',
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
                
            'tomorrow' => \Filament\Schemas\Components\Tabs\Tab::make('Tomorrow')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', today()->addDay()))
                ->badge(\App\Models\Booking::query()->whereDate('flight_date', today()->addDay())->count()),
        ];
    }
}
