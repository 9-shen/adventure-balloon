<?php

namespace App\Filament\Accountant\Resources\AccountantBookingResource\Pages;

use App\Filament\Accountant\Resources\AccountantBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountantBookings extends ListRecords
{
    protected static string $resource = AccountantBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No automated creation by accountants; they just manage finances.
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make('All Bookings'),
            
            'today' => \Filament\Resources\Components\Tab::make('Today')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', today()))
                ->badge(\App\Models\Booking::query()->whereDate('flight_date', today())->count()),
                
            'next_7_days' => \Filament\Resources\Components\Tab::make('Next 7 Days')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', '>=', today())->whereDate('flight_date', '<=', today()->addDays(7)))
                ->badge(\App\Models\Booking::query()->whereDate('flight_date', '>=', today())->whereDate('flight_date', '<=', today()->addDays(7))->count()),
                
            'upcoming' => \Filament\Resources\Components\Tab::make('Upcoming')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDate('flight_date', '>', today()))
                ->badge(\App\Models\Booking::query()->whereDate('flight_date', '>', today())->count()),
        ];
    }
}

