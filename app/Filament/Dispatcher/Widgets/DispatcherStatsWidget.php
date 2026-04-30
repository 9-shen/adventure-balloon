<?php

namespace App\Filament\Dispatcher\Widgets;

use App\Models\Booking;
use App\Models\Dispatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DispatcherStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        // Get the list of partner IDs this dispatcher manages
        $partnerIds = $user->managedPartners()->pluck('partners.id');

        $partnersCount = $partnerIds->count();
        $bookingsCount = Booking::whereIn('partner_id', $partnerIds)->count();
        $dispatchesCount = Dispatch::whereIn('booking_id', function ($query) use ($partnerIds) {
            $query->select('id')
                  ->from('bookings')
                  ->whereIn('partner_id', $partnerIds);
        })->count();

        return [
            Stat::make('Partners Managed', $partnersCount)
                ->description('Total partners assigned to you')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Total Bookings', $bookingsCount)
                ->description('Total bookings from your partners')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success'),
                
            Stat::make('Total Dispatches', $dispatchesCount)
                ->description('Total dispatches from your partners')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
        ];
    }
}
