<?php

namespace App\Filament\Admin\Resources\Vehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transportCompany.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('make')
                    ->label('Make')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model')
                    ->label('Model')
                    ->searchable(),

                TextColumn::make('plate_number')
                    ->label('Plate')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

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
                    ->sortable()
                    ->suffix(' seats'),

                TextColumn::make('price_per_trip')
                    ->label('Price/Trip')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('drivers_count')
                    ->label('Drivers')
                    ->counts('drivers')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('vehicle_type')
                    ->label('Type')
                    ->options([
                        'van'     => 'Van',
                        'minibus' => 'Minibus',
                        'bus'     => 'Bus',
                        'car'     => 'Car',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('make');
    }
}
