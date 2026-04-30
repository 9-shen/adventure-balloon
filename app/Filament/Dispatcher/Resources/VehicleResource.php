<?php

namespace App\Filament\Dispatcher\Resources;

use App\Filament\Admin\Resources\Vehicles\VehicleResource as BaseResource;
use App\Filament\Dispatcher\Resources\VehicleResource\Pages;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class VehicleResource extends BaseResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Directory';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        return $user?->hasRole('dispatcher') ?? false;
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

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'view' => Pages\ViewVehicle::route('/{record}'),
        ];
    }
}
