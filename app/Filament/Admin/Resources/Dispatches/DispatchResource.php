<?php

namespace App\Filament\Admin\Resources\Dispatches;

use App\Filament\Admin\Resources\Dispatches\Pages\CreateDispatch;
use App\Filament\Admin\Resources\Dispatches\Pages\EditDispatch;
use App\Filament\Admin\Resources\Dispatches\Pages\ListDispatches;
use App\Filament\Admin\Resources\Dispatches\Pages\ViewDispatch;
use App\Filament\Admin\Resources\Dispatches\Schemas\DispatchForm;
use App\Filament\Admin\Resources\Dispatches\Schemas\DispatchInfolist;
use App\Filament\Admin\Resources\Dispatches\Tables\DispatchesTable;
use App\Models\Dispatch;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DispatchResource extends Resource
{
    protected static ?string $model = Dispatch::class;

    protected static ?string $recordTitleAttribute = 'dispatch_ref';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Bookings';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Dispatches';
    }

    public static function form(Schema $form): Schema
    {
        return DispatchForm::configure($form);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return DispatchInfolist::configure($infolist);
    }

    public static function table(Table $table): Table
    {
        return DispatchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDispatches::route('/'),
            'create' => CreateDispatch::route('/create'),
            'edit'   => EditDispatch::route('/{record}/edit'),
            'view'   => ViewDispatch::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
    }
}
