<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Admin\Resources\Dispatches\Schemas\DispatchInfolist;
use App\Filament\Sales\Resources\DispatchResource\Pages\ListDispatches;
use App\Filament\Sales\Resources\DispatchResource\Pages\ViewDispatch;
use App\Filament\Admin\Resources\Dispatches\Tables\DispatchesTable;
use App\Models\Dispatch;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DispatchResource extends Resource
{
    protected static ?string $model = Dispatch::class;

    protected static ?string $recordTitleAttribute = 'dispatch_ref';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dispatches';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'My Bookings';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function canCreate(): bool   { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('sales') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('booking', function (Builder $q) {
                $q->where('created_by', Auth::id());
            });
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
            'index' => ListDispatches::route('/'),
            'view'  => ViewDispatch::route('/{record}'),
        ];
    }
}
