<?php

namespace App\Filament\Admin\Resources\TransportCompanies\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransportCompanyForm
{
    public static function make(Schema $form): Schema
    {
        return $form->components([

            Section::make('Company Information')
                ->icon('heroicon-o-building-office')
                ->columns(2)
                ->schema([
                    TextInput::make('company_name')
                        ->label('Company Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('contact_name')
                        ->label('Contact Person')
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('Phone / WhatsApp')
                        ->tel()
                        ->maxLength(50),

                    TextInput::make('address')
                        ->label('Address')
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Section::make('Banking Details')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->maxLength(255),

                    TextInput::make('bank_account')
                        ->label('Account Number')
                        ->maxLength(100),

                    TextInput::make('bank_iban')
                        ->label('IBAN')
                        ->maxLength(100)
                        ->columnSpanFull(),
                ]),

            Section::make('Status & Notes')
                ->icon('heroicon-o-cog-6-tooth')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Company Active')
                        ->default(true)
                        ->inline(false),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Logo')
                ->icon('heroicon-o-photo')
                ->collapsible()
                ->collapsed()
                ->schema([
                    SpatieMediaLibraryFileUpload::make('company-logo')
                        ->collection('company-logo')
                        ->label('Company Logo')
                        ->image()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('1:1')
                        ->maxSize(2048)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
