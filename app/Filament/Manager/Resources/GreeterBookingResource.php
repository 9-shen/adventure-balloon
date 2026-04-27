<?php

namespace App\Filament\Manager\Resources;

use App\Filament\Greeter\Resources\GreeterBookingResource as BaseResource;
use App\Filament\Manager\Resources\GreeterBookingResource\Pages\ListManagerGreeterBookings;
use App\Filament\Manager\Resources\GreeterBookingResource\Pages\ViewManagerGreeterBooking;
use App\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GreeterBookingResource extends BaseResource
{
    /**
     * Use a distinct slug so it doesn't clash with the manager's own BookingResource.
     */
    protected static ?string $slug = 'greeter-bookings';

    public static function getNavigationLabel(): string
    {
        return "Today's Bookings";
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Greeter';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    /**
     * Override to fix the ViewAction URL — the base resource points to the Greeter
     * panel's ViewGreeterBooking page; here we redirect to the manager-scoped page.
     */
    public static function table(Table $table): Table
    {
        // Get the base table configuration (columns, filters, bulk actions, etc.)
        $table = parent::table($table);

        // Replace the record actions with a corrected URL
        return $table->recordActions([
            ViewAction::make()
                ->url(fn (Booking $record): string => ViewManagerGreeterBooking::getUrl(['record' => $record]))
                ->label('Manage Attendance')
                ->icon('heroicon-o-user-group'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManagerGreeterBookings::route('/'),
            'view'  => ViewManagerGreeterBooking::route('/{record}'),
        ];
    }
}
