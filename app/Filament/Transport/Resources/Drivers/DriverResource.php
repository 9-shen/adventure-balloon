<?php

namespace App\Filament\Transport\Resources\Drivers;

use App\Filament\Transport\Resources\Drivers\Pages\CreateDriver;
use App\Filament\Transport\Resources\Drivers\Pages\EditDriver;
use App\Filament\Transport\Resources\Drivers\Pages\ListDrivers;
use App\Models\Driver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-identification';
    }

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Fleet Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Drivers';
    }

    // Scope to this transport company only
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return parent::getEloquentQuery()
            ->where('transport_company_id', $user->transport_company_id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Driver Information')
                ->description('Personal and contact details for this driver.')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('photo')
                        ->collection('license-documents')
                        ->label('Driver Photo / License')
                        ->image()
                        ->maxFiles(3)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone / WhatsApp')
                            ->tel()
                            ->required()
                            ->maxLength(50),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('national_id')
                            ->label('National ID')
                            ->maxLength(50),

                        TextInput::make('license_number')
                            ->label('License Number')
                            ->maxLength(100),

                        DatePicker::make('license_expiry')
                            ->label('License Expiry Date'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ]),

                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->collection('license-documents')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=Driver&color=d97706&background=fef3c7'),

                TextColumn::make('name')
                    ->label('Driver Name')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone / WhatsApp'),

                TextColumn::make('license_number')
                    ->label('License No.'),

                TextColumn::make('license_expiry')
                    ->label('Expiry')
                    ->date('d M Y')
                    ->color(fn($record) => $record->license_expiry?->isPast() ? 'danger' : ($record->isLicenseExpiringSoon() ? 'warning' : 'success')),

                TextColumn::make('vehicles_count')
                    ->label('Vehicles')
                    ->counts('vehicles')
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active Only'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDrivers::route('/'),
            'create' => CreateDriver::route('/create'),
            'edit'   => EditDriver::route('/{record}/edit'),
        ];
    }
}
