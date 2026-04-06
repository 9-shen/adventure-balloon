<?php

namespace App\Filament\Admin\Resources\Drivers;

use App\Filament\Admin\Resources\Drivers\Pages\CreateDriver;
use App\Filament\Admin\Resources\Drivers\Pages\EditDriver;
use App\Filament\Admin\Resources\Drivers\Pages\ListDrivers;
use App\Filament\Admin\Resources\Drivers\Pages\ViewDriver;
use App\Filament\Admin\Resources\Drivers\Schemas\DriverForm;
use App\Filament\Admin\Resources\Drivers\Tables\DriversTable;
use App\Models\Driver;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DriverResource extends Resource
{
    // Use string literal to avoid PHP 8.2 class-alias property type issue
    protected static ?string $model = \App\Models\Driver::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedIdentification;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Transport Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }

    // ─── Access Control ───────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    // ─── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $form): Schema
    {
        return DriverForm::make($form);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return DriversTable::make($table);
    }

    // ─── Soft Delete Scope ────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
                     ->withoutGlobalScope(SoftDeletingScope::class);
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListDrivers::route('/'),
            'create' => CreateDriver::route('/create'),
            'edit'   => EditDriver::route('/{record}/edit'),
            'view'   => ViewDriver::route('/{record}'),
        ];
    }
}
