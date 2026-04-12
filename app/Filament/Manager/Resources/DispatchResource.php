<?php

namespace App\Filament\Manager\Resources;

use App\Filament\Admin\Resources\Dispatches\DispatchResource as BaseResource;
use App\Filament\Manager\Resources\DispatchResource\Pages;

class DispatchResource extends BaseResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
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
