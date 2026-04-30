<?php

namespace App\Filament\Dispatcher\Resources;

use App\Filament\Admin\Resources\Dispatches\DispatchResource as BaseResource;
use App\Filament\Dispatcher\Resources\DispatchResource\Pages;

class DispatchResource extends BaseResource
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
        
        return parent::getEloquentQuery()->whereHas('booking', function ($q) use ($managedPartnerIds) {
            $q->whereIn('partner_id', $managedPartnerIds);
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispatches::route('/'),
            'create' => Pages\CreateDispatch::route('/create'),
            'edit' => Pages\EditDispatch::route('/{record}/edit'),
            'view' => Pages\ViewDispatch::route('/{record}'),
        ];
    }
}
