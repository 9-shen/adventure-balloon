<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->collection('avatar')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->searchable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable()
                    // Prevent toggling own active state
                    ->disabled(fn ($record) => $record?->id === Auth::id()),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('national_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nationality')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->label('Deleted At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make(),
                DeleteAction::make()
                    // Cannot delete yourself
                    ->hidden(fn ($record) => $record?->id === Auth::id())
                    // Cannot delete the only super_admin
                    ->hidden(fn ($record) => $record?->hasRole('super_admin') && \App\Models\User::role('super_admin')->count() <= 1),
                ForceDeleteAction::make()
                    ->hidden(fn ($record) => $record?->id === Auth::id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records, DeleteBulkAction $action) {
                            $records->each(function ($record) {
                                // Skip self and last super_admin
                                if ($record->id === Auth::id()) return;
                                if ($record->hasRole('super_admin') && \App\Models\User::role('super_admin')->count() <= 1) return;
                                $record->delete();
                            });
                        }),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records, ForceDeleteBulkAction $action) {
                            $records->each(function ($record) {
                                if ($record->id === Auth::id()) return;
                                $record->forceDelete();
                            });
                        }),
                ]),
            ]);
    }
}
