<?php

namespace App\Filament\Admin\Resources\Drivers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DriverForm
{
    public static function make(Schema $form): Schema
    {
        return $form->components([

            Section::make('Driver Information')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    Select::make('transport_company_id')
                        ->label('Transport Company')
                        ->relationship('transportCompany', 'company_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('Phone (WhatsApp)')
                        ->tel()
                        ->required()
                        ->maxLength(50)
                        ->helperText('Used for dispatch WhatsApp notifications'),

                    TextInput::make('national_id')
                        ->label('National ID (CIN)')
                        ->maxLength(100),

                    TextInput::make('license_number')
                        ->label('License Number')
                        ->maxLength(100),

                    DatePicker::make('license_expiry')
                        ->label('License Expiry Date')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                ]),

            Section::make('Status & Notes')
                ->icon('heroicon-o-cog-6-tooth')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Driver Active')
                        ->default(true)
                        ->inline(false),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('License Documents')
                ->icon('heroicon-o-document')
                ->collapsible()
                ->collapsed()
                ->schema([
                    SpatieMediaLibraryFileUpload::make('license-documents')
                        ->collection('license-documents')
                        ->label('License & ID Documents')
                        ->multiple()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                        ->maxFiles(5)
                        ->maxSize(4096)
                        ->downloadable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
