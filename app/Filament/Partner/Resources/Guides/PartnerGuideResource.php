<?php

namespace App\Filament\Partner\Resources\Guides;

use App\Filament\Partner\Resources\Guides\Pages\CreatePartnerGuide;
use App\Filament\Partner\Resources\Guides\Pages\EditPartnerGuide;
use App\Filament\Partner\Resources\Guides\Pages\ListPartnerGuides;
use App\Models\Guide;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PartnerGuideResource extends Resource
{
    protected static ?string $model = Guide::class;

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-user-group'; }
    public static function getNavigationLabel(): string { return 'Guides'; }
    public static function getNavigationGroup(): ?string { return 'My Team'; }
    public static function getNavigationSort(): ?int { return 2; }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('partner') ?? false;
    }

    /** Scope to current partner's guides only */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('partner_id', Auth::user()->partner_id);
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([
            \Filament\Forms\Components\TextInput::make('name')
                ->label('Full Name')
                ->required()
                ->maxLength(255),

            \Filament\Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique('guides', 'email', ignorable: fn ($record) => $record)
                ->maxLength(255)
                ->helperText('A guide portal account will be created automatically.'),

            \Filament\Forms\Components\TextInput::make('phone')
                ->label('Phone (WhatsApp)')
                ->tel()
                ->required()
                ->maxLength(50),

            \Filament\Forms\Components\TextInput::make('guide_reference')
                ->label('Guide Reference')
                ->required()
                ->maxLength(100)
                ->placeholder('e.g. GD-001')
                ->helperText('Must be unique within your agency.'),

            \Filament\Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->inline(false),

            \Filament\Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('guide_reference')
                    ->label('Guide Ref')
                    ->badge()
                    ->color('info'),

                TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->icon('heroicon-o-phone'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->alignCenter(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPartnerGuides::route('/'),
            'create' => CreatePartnerGuide::route('/create'),
            'edit'   => EditPartnerGuide::route('/{record}/edit'),
        ];
    }
}
