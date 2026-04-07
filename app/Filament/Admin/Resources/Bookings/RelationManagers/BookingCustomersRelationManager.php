<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingCustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    protected static ?string $title = 'Passengers';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(Schema $form): Schema
    {
        return $form->components([
            Select::make('type')
                ->label('Type')
                ->options(['adult' => 'Adult', 'child' => 'Child'])
                ->default('adult')
                ->required()
                ->native(false),

            TextInput::make('full_name')
                ->label('Full Name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->nullable(),

            TextInput::make('phone')
                ->tel()
                ->nullable(),

            TextInput::make('nationality')
                ->nullable(),

            TextInput::make('passport_number')
                ->label('Passport No.')
                ->nullable(),

            DatePicker::make('date_of_birth')
                ->label('Date of Birth')
                ->nullable()
                ->native(false),

            TextInput::make('weight_kg')
                ->label('Weight (kg)')
                ->numeric()
                ->suffix('kg')
                ->nullable(),

            Toggle::make('is_primary')
                ->label('Primary Contact')
                ->default(false)
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'adult' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable(),

                TextColumn::make('nationality')
                    ->label('Nationality')
                    ->toggleable(),

                TextColumn::make('weight_kg')
                    ->label('Weight')
                    ->suffix(' kg')
                    ->toggleable(),

                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Add Passenger'),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
