<?php

namespace App\Filament\Transport\Resources\Vehicles;

use App\Filament\Transport\Resources\Vehicles\Pages\CreateVehicle;
use App\Filament\Transport\Resources\Vehicles\Pages\EditVehicle;
use App\Filament\Transport\Resources\Vehicles\Pages\ListVehicles;
use App\Filament\Transport\Resources\Vehicles\RelationManagers\DriversRelationManager;
use App\Models\Vehicle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Fleet Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Vehicles';
    }

    // Scope to this transport company only
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return parent::getEloquentQuery()
            ->where('transport_company_id', $user->transport_company_id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vehicle Details')
                ->description('Add or update your vehicle information.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('make')
                            ->label('Make')
                            ->placeholder('e.g. Toyota')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('model')
                            ->label('Model')
                            ->placeholder('e.g. HiAce')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('plate_number')
                            ->label('License Plate')
                            ->required()
                            ->maxLength(20),

                        Select::make('vehicle_type')
                            ->label('Vehicle Type')
                            ->options([
                                'van'     => 'Van',
                                'minibus' => 'Minibus',
                                'bus'     => 'Bus',
                                'car'     => 'Car',
                            ])
                            ->required(),

                        TextInput::make('capacity')
                            ->label('Seat Capacity')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),

                        TextInput::make('price_per_trip')
                            ->label('Price per Trip (MAD)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('MAD')
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ]),

                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('make')
                    ->label('Make')
                    ->searchable(),

                TextColumn::make('model')
                    ->label('Model')
                    ->searchable(),

                TextColumn::make('plate_number')
                    ->label('Plate')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('vehicle_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => ucfirst($state))
                    ->color('primary'),

                TextColumn::make('capacity')
                    ->label('Seats')
                    ->suffix(' pax'),

                TextColumn::make('price_per_trip')
                    ->label('Price/Trip')
                    ->money('MAD'),

                TextColumn::make('drivers_count')
                    ->label('Drivers')
                    ->counts('drivers')
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active Only'),
            ])
            ->defaultSort('make');
    }

    public static function getRelations(): array
    {
        return [
            DriversRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'edit'   => EditVehicle::route('/{record}/edit'),
        ];
    }
}
