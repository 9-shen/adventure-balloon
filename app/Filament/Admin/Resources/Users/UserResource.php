<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Schemas\UserInfolist;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedUsers;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'User Management';
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->hasRole('super_admin') || $user?->hasRole('admin');
    }

    /**
     * A user cannot delete themselves.
     * A super_admin cannot be deleted if it is the last one.
     */
    public static function canDelete($record): bool
    {
        if ($record->id === Auth::id()) {
            return false;
        }

        if ($record->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return false;
        }

        return true;
    }

    public static function canForceDelete($record): bool
    {
        return $record->id !== Auth::id();
    }

    /**
     * Include soft-deleted records so the trashed filter works
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope(
            \Illuminate\Database\Eloquent\SoftDeletingScope::class
        );
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
