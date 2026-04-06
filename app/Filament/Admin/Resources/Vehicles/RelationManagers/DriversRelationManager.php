<?php

namespace App\Filament\Admin\Resources\Vehicles\RelationManagers;

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

class DriversRelationManager extends RelationManager
{
    protected static string $relationship = 'drivers';

    protected static ?string $title = 'Assigned Drivers';

    // AttachAction uses this to display driver names in the dropdown
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $form): Schema
    {
        // Extra pivot fields shown in the attach/edit modal
        return $form->components([
            Toggle::make('is_default')
                ->label('Set as Default Driver for this Vehicle')
                ->helperText('Mark this driver as the primary driver for dispatch assignment.')
                ->default(false)
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Driver Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->copyable()
                    ->icon('heroicon-o-phone'),

                TextColumn::make('transportCompany.company_name')
                    ->label('Company')
                    ->toggleable(),

                TextColumn::make('license_number')
                    ->label('License #')
                    ->toggleable(),

                TextColumn::make('license_expiry')
                    ->label('License Expiry')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record?->isLicenseExpiringSoon() ? 'danger' : null),

                IconColumn::make('pivot.is_default')
                    ->label('Default Driver')
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
                    ->label('Assign Driver')
                    ->modalHeading('Assign Driver to Vehicle')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        // Only show drivers from the same transport company
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
