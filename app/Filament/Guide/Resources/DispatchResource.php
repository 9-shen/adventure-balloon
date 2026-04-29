<?php

namespace App\Filament\Guide\Resources;

use App\Filament\Admin\Resources\Dispatches\Schemas\DispatchInfolist;
use App\Filament\Guide\Resources\DispatchResource\Pages\ListDispatches;
use App\Filament\Guide\Resources\DispatchResource\Pages\ViewDispatch;
use App\Filament\Guide\Resources\DispatchResource\Tables\DispatchesTable;
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

    // ─── Navigation ───────────────────────────────────────────────────────────

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

    // ─── Access Control ───────────────────────────────────────────────────────

    public static function canCreate(): bool   { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    // ─── Query scope — only dispatches for this guide's bookings ─────────────

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->whereHas('booking', function (Builder $q) use ($user) {
                $q->where('guide_id', $user->guide_id);
            });
    }

    // ─── Infolist — reuses the shared Admin schema ────────────────────────────

    public static function infolist(Schema $infolist): Schema
    {
        return DispatchInfolist::configure($infolist);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return DispatchesTable::configure($table);
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListDispatches::route('/'),
            'view'  => ViewDispatch::route('/{record}'),
        ];
    }
}
