<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Information')
                    ->description('Primary user account details and credentials.')
                    ->components([
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->collection('avatar')
                            ->avatar()
                            ->alignCenter()
                            ->columnSpanFull(),
                        
                        Grid::make(2)
                            ->components([
                                TextInput::make('name')
                                    ->required(),
                                
                                TextInput::make('email')
                                    ->label('Email address')
                                    ->email()
                                    ->required(),
                                
                                TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create'),
                            ]),
                    ]),

                Section::make('KYC & Demographics')
                    ->description('Verification data, contact details, and dates.')
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('phone')
                                    ->tel()
                                    ->default(null),
                                    
                                TextInput::make('national_id')
                                    ->label('National ID / Passport')
                                    ->default(null),
                                    
                                TextInput::make('nationality')
                                    ->default(null),
                                    
                                DatePicker::make('date_of_birth'),
                            ]),

                        Textarea::make('address')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Access Control')
                    ->description('Platform access attributes and role assignments.')
                    ->components([
                        Grid::make(3)
                            ->components([
                                Toggle::make('is_active')
                                    ->label('Account Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->required(),
                                
                                Select::make('roles')
                                    ->relationship('roles', 'name', fn ($query) => auth()->user()?->hasRole('super_admin') ? $query : $query->where('name', '!=', 'super_admin'))
                                    ->multiple()
                                    ->preload()
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
