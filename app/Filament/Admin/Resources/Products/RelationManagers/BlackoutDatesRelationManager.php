<?php

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class BlackoutDatesRelationManager extends RelationManager
{
    protected static string $relationship = 'blackoutDates';

    protected static ?string $title = 'Blackout Dates';

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                DatePicker::make('date')
                    ->label('Blocked Date')
                    ->required()
                    ->native(false),

                TextInput::make('reason')
                    ->label('Reason (optional)')
                    ->placeholder('e.g. National holiday, Maintenance')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date('D, d M Y')
                    ->sortable(),

                TextColumn::make('reason')
                    ->placeholder('No reason specified')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Add Blackout Date'),
            ]);
    }
}
