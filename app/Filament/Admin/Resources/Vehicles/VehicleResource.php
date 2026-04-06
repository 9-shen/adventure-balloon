<?php

namespace App\Filament\Admin\Resources\Vehicles;

use App\Filament\Admin\Resources\Vehicles\Pages\CreateVehicle;
use App\Filament\Admin\Resources\Vehicles\Pages\EditVehicle;
use App\Filament\Admin\Resources\Vehicles\Pages\ListVehicles;
use App\Filament\Admin\Resources\Vehicles\Pages\ViewVehicle;
use App\Filament\Admin\Resources\Vehicles\RelationManagers\DriversRelationManager;
use App\Filament\Admin\Resources\Vehicles\Schemas\VehicleForm;
use App\Filament\Admin\Resources\Vehicles\Tables\VehiclesTable;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedRocketLaunch;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Transport Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'plate_number';
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
        return VehicleForm::make($form);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return VehiclesTable::make($table);
    }

    // ─── Soft Delete Scope ────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
                     ->withoutGlobalScope(SoftDeletingScope::class);
    }

    // ─── Relation Managers ────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            DriversRelationManager::class,
        ];
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'edit'   => EditVehicle::route('/{record}/edit'),
            'view'   => ViewVehicle::route('/{record}'),
        ];
    }
}
