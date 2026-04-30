<?php

namespace App\Filament\Dispatcher\Resources;

use App\Filament\Admin\Resources\Bookings\BookingResource as BaseResource;
use App\Filament\Dispatcher\Resources\BookingResource\Pages;

class BookingResource extends BaseResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        return $user?->hasRole('dispatcher') ?? false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $managedPartnerIds = $user->managedPartners()->pluck('partners.id');
        return parent::getEloquentQuery()->whereIn('partner_id', $managedPartnerIds);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record = null): bool
    {
        return false;
    }

    public static function canDelete($record = null): bool
    {
        return false;
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return parent::table($table)
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
