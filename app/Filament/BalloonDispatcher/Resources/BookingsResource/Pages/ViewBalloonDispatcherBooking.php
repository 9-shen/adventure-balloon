<?php

namespace App\Filament\BalloonDispatcher\Resources\BookingsResource\Pages;

use App\Filament\BalloonDispatcher\Resources\BookingsResource\BookingsResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBalloonDispatcherBooking extends ViewRecord
{
    protected static string $resource = BookingsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
