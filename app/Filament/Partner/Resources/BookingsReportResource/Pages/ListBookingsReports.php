<?php

namespace App\Filament\Partner\Resources\BookingsReportResource\Pages;

use App\Filament\Partner\Resources\BookingsReportResource;
use App\Models\Booking;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListBookingsReports extends ListRecords
{
    protected static string $resource = BookingsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_csv')
                ->label('Export My Bookings (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new \App\Exports\PartnerBookingsExport($this->getFilteredTableQuery()),
                        'partner_bookings_report.csv',
                        \Maatwebsite\Excel\Excel::CSV
                    );
                }),
        ];
    }

    public function getTabs(): array
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $partnerId = $user->partner_id;

        $base = fn () => Booking::where('partner_id', $partnerId)->where('type', 'partner');

        return [
            'all' => Tab::make('All Bookings'),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereDate('flight_date', today()))
                ->badge($base()->whereDate('flight_date', today())->count()),

            'next_7_days' => Tab::make('Next 7 Days')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->whereDate('flight_date', '>=', today())
                    ->whereDate('flight_date', '<=', today()->addDays(7)))
                ->badge($base()
                    ->whereDate('flight_date', '>=', today())
                    ->whereDate('flight_date', '<=', today()->addDays(7))
                    ->count()),

            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('booking_status', 'confirmed'))
                ->badge($base()->where('booking_status', 'confirmed')->count()),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('booking_status', 'pending'))
                ->badge($base()->where('booking_status', 'pending')->count()),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('booking_status', 'completed'))
                ->badge($base()->where('booking_status', 'completed')->count()),
        ];
    }
}
