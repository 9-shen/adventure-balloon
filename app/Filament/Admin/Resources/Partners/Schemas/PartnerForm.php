<?php

namespace App\Filament\Admin\Resources\Partners\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Company Information')
                    ->description('Core company identity and contact details.')
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('company_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                TextInput::make('trade_name')
                                    ->label('Trade / Commercial Name')
                                    ->maxLength(255)
                                    ->placeholder('If different from company name'),

                                TextInput::make('registration_number')
                                    ->label('Business Registration No.')
                                    ->maxLength(100),

                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(50),

                                TextInput::make('city')
                                    ->maxLength(100),

                                TextInput::make('country')
                                    ->maxLength(100),
                            ]),

                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Tax & Legal')
                    ->description('Tax identification and legal registration.')
                    ->collapsed()
                    ->components([
                        TextInput::make('tax_number')
                            ->label('Tax Number (ICE / TVA)')
                            ->maxLength(100),
                    ]),

                Section::make('Banking Details')
                    ->description('Used for invoice generation and payment processing.')
                    ->collapsed()
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('bank_name')
                                    ->maxLength(255),

                                TextInput::make('bank_account')
                                    ->label('Account Number')
                                    ->maxLength(100),

                                TextInput::make('bank_iban')
                                    ->label('IBAN')
                                    ->maxLength(100),

                                TextInput::make('bank_swift')
                                    ->label('SWIFT / BIC')
                                    ->maxLength(50),

                                TextInput::make('payment_terms_days')
                                    ->label('Payment Terms (days)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('days')
                                    ->default(30),
                            ]),
                    ]),

                Section::make('Status & Account')
                    ->description('Partner approval status and platform access.')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('status')
                                    ->options([
                                        'pending'  => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->native(false),

                                Toggle::make('is_active')
                                    ->label('Partner Active')
                                    ->default(true)
                                    ->inline(false),

                                DateTimePicker::make('approved_at')
                                    ->label('Approved At')
                                    ->native(false)
                                    ->nullable(),
                            ]),

                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Internal notes about this partner...'),
                    ]),

                Section::make('KYC Documents')
                    ->description('Upload partner identification and registration documents (PDF, JPEG, PNG).')
                    ->components([
                        SpatieMediaLibraryFileUpload::make('kyc-documents')
                            ->collection('kyc-documents')
                            ->multiple()
                            ->reorderable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxFiles(20)
                            ->columnSpanFull(),
                    ]),

                // ── Portal Access ──────────────────────────────────────────────────────────
                Section::make('Portal Access')
                    ->description('Assign a user account (with "partner" role) so they can log in at /partner.')
                    ->collapsed()
                    ->components([
                        Select::make('portal_user_id')
                            ->label('Linked Portal User')
                            ->placeholder('Search by name or email...')
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->hint('Only users with the "partner" role are listed.')
                            ->getSearchResultsUsing(fn (string $search) =>
                                User::role('partner')
                                    ->where(function ($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                          ->orWhere('email', 'like', "%{$search}%");
                                    })
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->email})"])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value) =>
                                optional(User::find($value))->name
                                    . ' (' . optional(User::find($value))->email . ')'
                            ),
                    ]),

            ]);
    }
}
