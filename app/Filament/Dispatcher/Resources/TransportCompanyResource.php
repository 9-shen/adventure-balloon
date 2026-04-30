<?php

namespace App\Filament\Dispatcher\Resources;

use App\Filament\Admin\Resources\TransportCompanies\TransportCompanyResource as BaseResource;
use App\Filament\Dispatcher\Resources\TransportCompanyResource\Pages;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class TransportCompanyResource extends BaseResource
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
            'index' => Pages\ListTransportCompanies::route('/'),
            'view' => Pages\ViewTransportCompany::route('/{record}'),
        ];
    }
}
