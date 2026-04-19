<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Basic Information')
                    ->components([
                        TextEntry::make('name')
                            ->columnSpanFull(),

                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('No description provided.'),
                    ]),
                Grid::make(1)
                    ->schema([
                        Section::make('Images')
                            ->components([
                                SpatieMediaLibraryImageEntry::make('product-images')
                                    ->collection('product-images')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Pricing')
                            ->columns(2)
                            ->components([
                                TextEntry::make('base_adult_price')
                                    ->label('Adult Price')
                                    ->money('MAD'),

                                TextEntry::make('base_child_price')
                                    ->label('Child Price')
                                    ->money('MAD'),
                            ]),
                    ]),





                Section::make('Details')
                    ->columns(2)
                    ->components([
                        TextEntry::make('duration_minutes')
                            ->label('Duration')
                            ->suffix(' min')
                            ->placeholder('Not specified'),

                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),



                Section::make('System')
                    ->columns(3)
                    ->components([
                        TextEntry::make('created_at')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->dateTime(),

                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->placeholder('Not deleted'),
                    ]),
            ]);
    }
}
