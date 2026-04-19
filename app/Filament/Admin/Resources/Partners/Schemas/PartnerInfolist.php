<?php

namespace App\Filament\Admin\Resources\Partners\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Grid::make(1)
                    ->schema([


                        Section::make('Company Information')
                            ->columns(2)
                            ->components([
                                TextEntry::make('company_name')
                                    ->columnSpan(2),

                                TextEntry::make('trade_name')
                                    ->label('Trade Name')
                                    ->placeholder('—'),

                                TextEntry::make('registration_number')
                                    ->label('Registration No.')
                                    ->placeholder('—'),

                                TextEntry::make('email')
                                    ->copyable(),

                                TextEntry::make('phone')
                                    ->placeholder('—'),

                                TextEntry::make('city')
                                    ->placeholder('—'),

                                TextEntry::make('country')
                                    ->placeholder('—'),

                                TextEntry::make('address')
                                    ->columnSpan(2)
                                    ->placeholder('—'),
                            ]),

                        Section::make('Tax & Legal')
                            ->columns(2)
                            ->components([
                                TextEntry::make('tax_number')
                                    ->label('Tax Number')
                                    ->placeholder('—'),
                            ]),

                        Section::make('KYC Documents')
                            ->components([
                                SpatieMediaLibraryImageEntry::make('kyc-documents')
                                    ->collection('kyc-documents')
                                    ->columnSpanFull(),
                            ]),

                    ]),

                Grid::make(1)
                    ->schema([



                        Section::make('Banking')
                            ->columns(2)
                            ->components([
                                TextEntry::make('bank_name')
                                    ->placeholder('—'),

                                TextEntry::make('bank_account')
                                    ->label('Account No.')
                                    ->placeholder('—'),

                                TextEntry::make('bank_iban')
                                    ->label('IBAN')
                                    ->placeholder('—')
                                    ->copyable(),

                                TextEntry::make('bank_swift')
                                    ->label('SWIFT / BIC')
                                    ->placeholder('—'),

                                TextEntry::make('payment_terms_days')
                                    ->label('Payment Terms')
                                    ->suffix(' days'),
                            ]),
                        Section::make('Status')
                            ->columns(3)
                            ->components([
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default    => 'warning',
                                    }),

                                IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),

                                TextEntry::make('approved_at')
                                    ->dateTime()
                                    ->placeholder('Not approved yet'),

                                TextEntry::make('notes')
                                    ->columnSpan(3)
                                    ->placeholder('No notes.'),
                            ]),
                        Section::make('System')
                            ->columns(3)
                            ->components([
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                                TextEntry::make('deleted_at')->dateTime()->placeholder('Not deleted'),
                            ]),
                    ]),




            ]);
    }
}
