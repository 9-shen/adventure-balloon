<?php

namespace App\Filament\Admin\Resources\Vehicles\Schemas;

use App\Models\TransportCompany;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function make(Schema $form): Schema
    {
        return $form->components([

            Section::make('Vehicle Information')
                ->icon('heroicon-o-truck')
                ->columns(2)
                ->schema([
                    Select::make('transport_company_id')
                        ->label('Transport Company')
                        ->relationship('transportCompany', 'company_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('make')
                        ->label('Make')
                        ->placeholder('e.g. Mercedes')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('model')
                        ->label('Model')
                        ->placeholder('e.g. Sprinter')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('plate_number')
                        ->label('Plate Number')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    Select::make('vehicle_type')
                        ->label('Vehicle Type')
                        ->options([
                            'van'     => 'Van',
                            'minibus' => 'Minibus',
                            'bus'     => 'Bus',
                            'car'     => 'Car',
                        ])
                        ->default('van')
                        ->required(),

                    TextInput::make('capacity')
                        ->label('Passenger Capacity')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('seats'),

                    TextInput::make('price_per_trip')
                        ->label('Price Per Trip (MAD)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('MAD')
                        ->default(0),
                ]),

            Section::make('Status & Notes')
                ->icon('heroicon-o-cog-6-tooth')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Vehicle Active')
                        ->default(true)
                        ->inline(false),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(9)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
