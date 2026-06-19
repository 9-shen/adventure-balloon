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
                    ->label(fn() => 'Adult Price (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix(fn() => app(\App\Settings\AppSettings::class)->getIsoCurrency())
                    ->required()
                    ->default(0.00),

                TextInput::make('partner_child_price')
                    ->label(fn() => 'Child Price (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix(fn() => app(\App\Settings\AppSettings::class)->getIsoCurrency())
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
                    ->label(fn() => 'Base Adult (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                    ->money()
                    ->color('gray'),

                TextColumn::make('pivot.partner_adult_price')
                    ->label(fn() => 'Partner Adult (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                    ->money(),

                TextColumn::make('base_child_price')
                    ->label(fn() => 'Base Child (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                    ->money()
                    ->color('gray'),

                TextColumn::make('pivot.partner_child_price')
                    ->label(fn() => 'Partner Child (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                    ->money(),

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
                            ->label(fn() => 'Adult Price (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix(fn() => app(\App\Settings\AppSettings::class)->getIsoCurrency())
                            ->required()
                            ->default(0.00),

                        TextInput::make('partner_child_price')
                            ->label(fn() => 'Child Price (' . app(\App\Settings\AppSettings::class)->getIsoCurrency() . ')')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix(fn() => app(\App\Settings\AppSettings::class)->getIsoCurrency())
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
