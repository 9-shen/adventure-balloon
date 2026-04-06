<?php

namespace App\Filament\Admin\Resources\Drivers\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    protected static ?string $title = 'Assigned Vehicles';

    // AttachAction uses this to display vehicle plates in the dropdown
    protected static ?string $recordTitleAttribute = 'plate_number';

    public function form(Schema $form): Schema
    {
        // Extra pivot fields shown in the attach/edit modal
        return $form->components([
            Toggle::make('is_default')
                ->label('Set as Default Vehicle for this Driver')
                ->helperText('Mark this as the driver\'s primary assigned vehicle for dispatch.')
                ->default(false)
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plate_number')
                    ->label('Plate')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('make')
                    ->label('Make')
                    ->sortable(),

                TextColumn::make('model')
                    ->label('Model'),

                TextColumn::make('vehicle_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'van'     => 'info',
                        'minibus' => 'warning',
                        'bus'     => 'success',
                        'car'     => 'gray',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('capacity')
                    ->label('Seats')
                    ->suffix(' seats'),

                IconColumn::make('pivot.is_default')
                    ->label('Default Vehicle')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()->label('Set as Default'),
                DetachAction::make()->label('Remove'),
            ])
            ->toolbarActions([
                AttachAction::make()
                    ->label('Assign Vehicle')
                    ->modalHeading('Assign Vehicle to Driver')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        // Only show vehicles from the same transport company
                        fn ($query) => $query->where(
                            'transport_company_id',
                            $this->getOwnerRecord()->transport_company_id
                        )
                    ),
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Remove Selected'),
                ]),
            ]);
    }
}
