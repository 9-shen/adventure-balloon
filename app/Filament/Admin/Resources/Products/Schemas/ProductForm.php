<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Basic Information')
                    ->description('Product name and description visible to staff.')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing')
                    ->description('Base prices used for all regular bookings. Partner overrides are managed in Phase 5.')
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('base_adult_price')
                                    ->label('Adult Price (MAD)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('MAD')
                                    ->required()
                                    ->default(0.00),

                                TextInput::make('base_child_price')
                                    ->label('Child Price (MAD)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('MAD')
                                    ->required()
                                    ->default(0.00),
                            ]),
                    ]),

                Section::make('Details')
                    ->description('Optional operational details.')
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('duration_minutes')
                                    ->label('Duration (minutes)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->suffix('min')
                                    ->placeholder('e.g. 60')
                                    ->default(null),

                                Toggle::make('is_active')
                                    ->label('Product Active')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),

                Section::make('Product Images')
                    ->description('Upload one or more images for this product. Used on partner portal and booking confirmations.')
                    ->components([
                        SpatieMediaLibraryFileUpload::make('product-images')
                            ->collection('product-images')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imagePreviewHeight('120')
                            ->panelLayout('grid')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxFiles(10)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
