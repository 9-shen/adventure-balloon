<?php

namespace App\Filament\Admin\Resources\BalloonDispatchers;

use App\Filament\Admin\Resources\BalloonDispatchers\Pages\CreateBalloonDispatcher;
use App\Filament\Admin\Resources\BalloonDispatchers\Pages\EditBalloonDispatcher;
use App\Filament\Admin\Resources\BalloonDispatchers\Pages\ListBalloonDispatchers;
use App\Models\BalloonDispatcher;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BalloonDispatcherResource extends Resource
{
    protected static ?string $model = BalloonDispatcher::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function getNavigationLabel(): string { return 'Balloon Dispatchers'; }
    public static function getNavigationGroup(): ?string { return 'User Management'; }
    public static function getNavigationSort(): ?int { return 5; }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Section::make('Profile')->components([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email (Login)')
                    ->email()
                    ->required()
                    ->unique(BalloonDispatcher::class, 'email', ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('A portal account will be created automatically.'),

                TextInput::make('phone')
                    ->label('Phone / WhatsApp')
                    ->tel()
                    ->nullable()
                    ->maxLength(50),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),

                Textarea::make('notes')
                    ->label('Notes')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
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
                    ->weight('semibold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                IconColumn::make('has_portal_account')
                    ->label('Portal Account')
                    ->boolean()
                    ->getStateUsing(fn (BalloonDispatcher $record): bool => $record->hasPortalAccount()),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBalloonDispatchers::route('/'),
            'create' => CreateBalloonDispatcher::route('/create'),
            'edit'   => EditBalloonDispatcher::route('/{record}/edit'),
        ];
    }
}
