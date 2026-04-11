<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\Partner;
use App\Models\TransportCompany;
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
                                    ->live()
                                    ->columnSpan(2),
                            ]),

                        Select::make('partner_id')
                            ->label('Partner Company')
                            ->options(Partner::where('status', 'approved')->pluck('company_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Select partner...')
                            ->visible(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $roles = (array) $get('roles');
                                if (empty($roles)) return false;
                                $partnerRoles = \Spatie\Permission\Models\Role::where('name', 'partner')->pluck('id')->toArray();
                                return count(array_intersect($roles, $partnerRoles)) > 0 || in_array('partner', $roles);
                            })
                            ->helperText('Link this user to a partner company for partner portal access.'),

                        Select::make('transport_company_id')
                            ->label('Transport Company')
                            ->options(TransportCompany::where('is_active', true)->pluck('company_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Select transport company...')
                            ->visible(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $roles = (array) $get('roles');
                                if (empty($roles)) return false;
                                $transportRoles = \Spatie\Permission\Models\Role::where('name', 'transport')->pluck('id')->toArray();
                                return count(array_intersect($roles, $transportRoles)) > 0 || in_array('transport', $roles);
                            })
                            ->helperText('Link this user to a transport company for transport portal access.'),
                            
                        Select::make('driver_id')
                            ->label('Linked Driver')
                            ->options(\App\Models\Driver::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Select driver...')
                            ->visible(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $roles = (array) $get('roles');
                                if (empty($roles)) return false;
                                $driverRoles = \Spatie\Permission\Models\Role::where('name', 'driver')->pluck('id')->toArray();
                                return count(array_intersect($roles, $driverRoles)) > 0 || in_array('driver', $roles);
                            })
                            ->helperText('Link this user to a driver profile for the driver portal.'),
                    ]),
            ]);
    }
}
