<?php

namespace App\Filament\Admin\Resources\TransportCompanies;

use App\Filament\Admin\Resources\TransportCompanies\Pages\CreateTransportCompany;
use App\Filament\Admin\Resources\TransportCompanies\Pages\EditTransportCompany;
use App\Filament\Admin\Resources\TransportCompanies\Pages\ListTransportCompanies;
use App\Filament\Admin\Resources\TransportCompanies\Pages\ViewTransportCompany;
use App\Filament\Admin\Resources\TransportCompanies\RelationManagers\DriversRelationManager;
use App\Filament\Admin\Resources\TransportCompanies\RelationManagers\VehiclesRelationManager;
use App\Filament\Admin\Resources\TransportCompanies\Schemas\TransportCompanyForm;
use App\Filament\Admin\Resources\TransportCompanies\Tables\TransportCompaniesTable;
use App\Models\TransportCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TransportCompanyResource extends Resource
{
    protected static ?string $model = TransportCompany::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedTruck;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Transport Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'company_name';
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
        return TransportCompanyForm::make($form);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return TransportCompaniesTable::make($table);
    }

    // ─── Relation Managers ────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            VehiclesRelationManager::class,
            DriversRelationManager::class,
        ];
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
            'index'  => ListTransportCompanies::route('/'),
            'create' => CreateTransportCompany::route('/create'),
            'edit'   => EditTransportCompany::route('/{record}/edit'),
            'view'   => ViewTransportCompany::route('/{record}'),
        ];
    }
}
