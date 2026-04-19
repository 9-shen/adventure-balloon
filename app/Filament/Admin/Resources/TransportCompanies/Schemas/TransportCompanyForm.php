<?php

namespace App\Filament\Admin\Resources\TransportCompanies\Schemas;

use App\Models\User;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
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
            Grid::make(1)
                ->schema([
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
                                ->placeholder('[Country Code][Number] eg: 212666777888')
                                ->tel()
                                ->maxLength(50)
                                ->columnSpanFull(),

                            TextInput::make('address')
                                ->label('Address')
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ]),
                    Section::make('Logo')
                        ->icon('heroicon-o-photo')
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
                ]),
            Grid::make(1)
                ->schema([
                    Section::make('Banking Details')
                        ->icon('heroicon-o-banknotes')
                        ->columns(2)
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



                    // ── Portal Access ──────────────────────────────────────────────────────
                    Section::make('Portal Access')
                        ->description('Assign a user account (with "transport" role) so they can log in at /transport.')
                        ->icon('heroicon-o-key')
                        ->schema([
                            Select::make('portal_user_id')
                                ->label('Linked Portal User')
                                ->placeholder('Search by name or email...')
                                ->searchable()
                                ->nullable()
                                ->native(false)
                                ->hint('Only users with the "transport" role are listed.')
                                ->getSearchResultsUsing(
                                    fn(string $search) =>
                                    User::role('transport')
                                        ->where(function ($q) use ($search) {
                                            $q->where('name', 'like', "%{$search}%")
                                                ->orWhere('email', 'like', "%{$search}%");
                                        })
                                        ->limit(20)
                                        ->get()
                                        ->mapWithKeys(fn($u) => [$u->id => "{$u->name} ({$u->email})"])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(
                                    fn($value) =>
                                    optional(User::find($value))->name
                                        . ' (' . optional(User::find($value))->email . ')'
                                ),
                        ]),
                ]),
        ]);
    }
}
