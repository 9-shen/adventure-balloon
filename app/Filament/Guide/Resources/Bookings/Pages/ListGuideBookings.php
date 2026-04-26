<?php

namespace App\Filament\Guide\Resources\Bookings\Pages;

use App\Filament\Guide\Resources\Bookings\GuideBookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGuideBookings extends ListRecords
{
    protected static string $resource = GuideBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New Booking')];
    }
}
