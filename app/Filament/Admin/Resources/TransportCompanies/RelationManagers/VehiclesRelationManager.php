<?php

namespace App\Filament\Admin\Resources\TransportCompanies\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    protected static ?string $title = 'Vehicles';

    protected static ?string $recordTitleAttribute = 'plate_number';

    public function form(Schema $form): Schema
    {
        return $form->components([
            TextInput::make('make')
                ->label('Make')
                ->required()
                ->maxLength(100),

            TextInput::make('model')
                ->label('Model')
                ->required()
                ->maxLength(100),

            TextInput::make('plate_number')
                ->label('Plate Number')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),

            Select::make('vehicle_type')
                ->label('Type')
                ->options([
                    'van'     => 'Van',
                    'minibus' => 'Minibus',
                    'bus'     => 'Bus',
                    'car'     => 'Car',
                ])
                ->required()
                ->default('van'),

            TextInput::make('capacity')
                ->label('Capacity (seats)')
                ->numeric()
                ->minValue(1)
                ->required(),

            TextInput::make('price_per_trip')
                ->label('Price Per Trip (MAD)')
                ->numeric()
                ->prefix('MAD')
                ->default(0),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('make')->label('Make')->searchable(),
                TextColumn::make('model')->label('Model'),
                TextColumn::make('plate_number')
                    ->label('Plate')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('vehicle_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('capacity')
                    ->label('Seats')
                    ->suffix(' seats'),
                TextColumn::make('price_per_trip')
                    ->label('Price/Trip')
                    ->money('MAD'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Add Vehicle'),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
