<?php

namespace App\Filament\Admin\Resources\TransportCompanies\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DriversRelationManager extends RelationManager
{
    protected static string $relationship = 'drivers';

    protected static ?string $title = 'Drivers';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $form): Schema
    {
        return $form->components([
            TextInput::make('name')
                ->label('Full Name')
                ->required()
                ->maxLength(255),

            TextInput::make('phone')
                ->label('Phone (WhatsApp)')
                ->tel()
                ->required()
                ->maxLength(50),

            TextInput::make('national_id')
                ->label('National ID (CIN)')
                ->maxLength(100),

            TextInput::make('license_number')
                ->label('License Number')
                ->maxLength(100),

            DatePicker::make('license_expiry')
                ->label('License Expiry')
                ->native(false)
                ->displayFormat('d/m/Y'),

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
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                TextColumn::make('license_number')
                    ->label('License #')
                    ->toggleable(),
                TextColumn::make('license_expiry')
                    ->label('Expiry')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record?->isLicenseExpiringSoon() ? 'danger' : null),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()->label('Add Driver'),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
