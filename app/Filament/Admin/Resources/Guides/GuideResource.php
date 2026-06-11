<?php

namespace App\Filament\Admin\Resources\Guides;

use App\Filament\Admin\Resources\Guides\Pages\CreateGuide;
use App\Filament\Admin\Resources\Guides\Pages\EditGuide;
use App\Filament\Admin\Resources\Guides\Pages\ListGuides;
use App\Models\Guide;
use App\Models\Partner;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GuideResource extends Resource
{
    protected static ?string $model = Guide::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Partner Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'Guides';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->components(self::getFormComponents());
    }

    /**
     * Shared form components — reused by GuidesRelationManager (without partner picker).
     */
    public static function getFormComponents(bool $withPartner = true): array
    {
        $components = [];

        if ($withPartner) {
            $components[] = \Filament\Forms\Components\Select::make('partner_id')
                ->label('Partner')
                ->options(Partner::where('is_active', true)->orderBy('company_name')->pluck('company_name', 'id'))
                ->required()
                ->native(false)
                ->searchable();
        }

        return array_merge($components, [
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
                ->helperText('Used for portal login. Account created automatically.'),

            \Filament\Forms\Components\TextInput::make('phone')
                ->label('Phone (WhatsApp)')
                ->tel()
                ->required()
                ->maxLength(50)
                ->placeholder('+212669611393 | Country Code | Number'),

            \Filament\Forms\Components\TextInput::make('guide_reference')
                ->label('Guide Reference')
                ->required()
                ->maxLength(100)
                ->placeholder('e.g. GD-001')
                ->helperText('Must be unique within the partner.'),

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
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('partner.company_name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable(),

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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGuides::route('/'),
            'create' => CreateGuide::route('/create'),
            'edit'   => EditGuide::route('/{record}/edit'),
        ];
    }
}
