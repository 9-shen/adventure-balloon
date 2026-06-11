<?php

namespace App\Filament\Manager\Resources;

use App\Filament\Admin\Resources\BalloonDispatches\BalloonDispatchResource as BaseResource;
use App\Filament\Manager\Resources\BalloonDispatchResource\Pages;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BalloonDispatchResource extends BaseResource
{
    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
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
            'index' => Pages\ListBalloonDispatches::route('/'),
            'view' => Pages\ViewBalloonDispatch::route('/{record}'),
        ];
    }
}
