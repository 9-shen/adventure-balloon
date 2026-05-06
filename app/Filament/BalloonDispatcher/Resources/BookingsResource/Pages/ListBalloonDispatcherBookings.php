<?php

namespace App\Filament\BalloonDispatcher\Resources\BookingsResource\Pages;

use App\Filament\BalloonDispatcher\Resources\BookingsResource\BookingsResource;
use App\Models\Booking;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBalloonDispatcherBookings extends ListRecords
{
    protected static string $resource = BookingsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'today' => Tab::make('Today')
                ->icon('heroicon-o-sun')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('flight_date', today()))
                ->badge(Booking::whereDate('flight_date', today())->count()),

            'tomorrow' => Tab::make('Tomorrow')
                ->icon('heroicon-o-arrow-right-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('flight_date', today()->addDay()))
                ->badge(Booking::whereDate('flight_date', today()->addDay())->count()),

            'next_7_days' => Tab::make('Next 7 Days')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('flight_date', [
                    today()->addDay(),
                    today()->addDays(7),
                ]))
                ->badge(Booking::whereBetween('flight_date', [today()->addDay(), today()->addDays(7)])->count()),

            'upcoming' => Tab::make('Upcoming')
                ->icon('heroicon-o-rocket-launch')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('flight_date', '>', today()))
                ->badge(Booking::whereDate('flight_date', '>', today())->count()),

            'all' => Tab::make('All')
                ->icon('heroicon-o-list-bullet'),
        ];
    }
}
