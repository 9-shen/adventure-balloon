<?php

namespace App\Filament\Manager\Resources;

use App\Filament\Admin\Resources\Bookings\BookingResource as BaseResource;
use App\Filament\Manager\Resources\BookingResource\Pages;

class BookingResource extends BaseResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
