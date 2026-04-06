<?php

namespace App\Filament\Admin\Resources\Partners\RelationManagers;

use App\Models\Product;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;

class PartnerProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Product Pricing';

    // Tells AttachAction to use the 'name' column as the dropdown label
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Form used for the pivot fields on AttachAction (Add) and EditAction (Update pricing).
     */
    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                TextInput::make('partner_adult_price')
                    ->label('Adult Price (MAD)')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('MAD')
                    ->required()
                    ->default(0.00),

                TextInput::make('partner_child_price')
                    ->label('Child Price (MAD)')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('MAD')
                    ->required()
                    ->default(0.00),

                Toggle::make('is_active')
                    ->label('Pricing Active')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('base_adult_price')
                    ->label('Base Adult (MAD)')
                    ->money('MAD')
                    ->color('gray'),

                TextColumn::make('pivot.partner_adult_price')
                    ->label('Partner Adult (MAD)')
                    ->money('MAD'),

                TextColumn::make('base_child_price')
                    ->label('Base Child (MAD)')
                    ->money('MAD')
                    ->color('gray'),

                TextColumn::make('pivot.partner_child_price')
                    ->label('Partner Child (MAD)')
                    ->money('MAD'),

                IconColumn::make('pivot.is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit Pricing'),
                DetachAction::make()
                    ->label('Remove'),
            ])
            ->toolbarActions([
                AttachAction::make()
                    ->label('Assign Product Pricing')
                    ->preloadRecordSelect()   // loads Product options
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Product')
                            ->searchable()
                            ->preload(),

                        TextInput::make('partner_adult_price')
                            ->label('Adult Price (MAD)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('MAD')
                            ->required()
                            ->default(0.00),

                        TextInput::make('partner_child_price')
                            ->label('Child Price (MAD)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('MAD')
                            ->required()
                            ->default(0.00),

                        Toggle::make('is_active')
                            ->label('Pricing Active')
                            ->default(true)
                            ->inline(false),
                    ]),

                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Remove selected'),
                ]),
            ]);
    }
}
