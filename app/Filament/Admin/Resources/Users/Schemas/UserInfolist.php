<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Information')
                    ->columns(2)
                    ->components([
                        SpatieMediaLibraryImageEntry::make('avatar')
                            ->collection('avatar')
                            ->circular()
                            ->columnSpanFull(),
                        TextEntry::make('name'),
                        TextEntry::make('email')->label('Email address'),
                        TextEntry::make('phone'),
                        TextEntry::make('roles.name')->badge(),
                        IconEntry::make('is_active')->label('Account Active')->boolean(),
                    ]),
                
                Section::make('KYC Data')
                    ->columns(3)
                    ->components([
                        TextEntry::make('national_id')->label('National ID / Passport'),
                        TextEntry::make('nationality'),
                        TextEntry::make('date_of_birth')->date(),
                        TextEntry::make('address')->columnSpanFull(),
                    ]),

                Section::make('System Variables')
                    ->columns(2)
                    ->components([
                        TextEntry::make('last_login_at')->dateTime(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),

                Section::make('Computed Permissions')
                    ->description('Granular permissions inherited through roles or assigned directly.')
                    ->components([
                        TextEntry::make('permissions')
                            ->label('Effective Permissions')
                            ->default(fn ($record) => $record->getAllPermissions()->pluck('name')->join(', ') ?: 'No advanced permissions')
                            ->badge()
                            ->separator(', ')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
