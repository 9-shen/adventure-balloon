<?php

namespace App\Filament\Manager\Resources;

use App\Filament\Admin\Resources\Products\ProductResource as BaseResource;
use App\Filament\Manager\Resources\ProductResource\Pages;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends BaseResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Directory';
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
            ->columns([
                SpatieMediaLibraryImageColumn::make('product-images')
                    ->collection('product-images')
                    ->label('Image')
                    ->circular(false)
                    ->conversion('thumb')
                    ->limit(1),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('base_adult_price')
                    ->label('Adult (MAD)')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('base_child_price')
                    ->label('Child (MAD)')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Read-only icon instead of editable toggle
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
        ];
    }
}
