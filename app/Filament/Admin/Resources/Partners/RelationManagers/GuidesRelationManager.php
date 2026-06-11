<?php

namespace App\Filament\Admin\Resources\Partners\RelationManagers;

use App\Models\Guide;
use App\Models\User;
use App\Notifications\GuideAccountCreatedNotification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GuidesRelationManager extends RelationManager
{
    protected static string $relationship = 'guides';
    protected static ?string $title = 'Guides';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $form): Schema
    {
        return $form->components([
            TextInput::make('name')
                ->label('Full Name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique('guides', 'email', ignorable: fn ($record) => $record)
                ->maxLength(255)
                ->helperText('Used for portal login. Account created automatically.'),

            TextInput::make('phone')
                ->label('Phone (WhatsApp)')
                ->tel()
                ->required()
                ->maxLength(50)
                ->placeholder('+212669611393 | Country Code | Number'),

            TextInput::make('guide_reference')
                ->label('Guide Reference')
                ->required()
                ->maxLength(100)
                ->placeholder('e.g. GD-001')
                ->helperText('Must be unique within this partner.'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->inline(false),

            Textarea::make('notes')
                ->label('Notes')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
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
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label('Add Guide')
                    ->after(function (Guide $record): void {
                        $this->createGuidePortalAccount($record);
                    }),

                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─── Auto-create guide portal account ────────────────────────────────────

    private function createGuidePortalAccount(Guide $guide): void
    {
        $rawPassword = '1234567890';

        $user = User::firstOrCreate(
            ['email' => $guide->email],
            [
                'name'       => $guide->name,
                'password'   => Hash::make($rawPassword),
                'phone'      => $guide->phone,
                'is_active'  => true,
                'guide_id'   => $guide->id,
                'partner_id' => $guide->partner_id,
            ]
        );

        if (! $user->hasRole('guide')) {
            $user->assignRole('guide');
        }

        try {
            $guide->notify(new GuideAccountCreatedNotification($guide->name, $guide->email, $rawPassword));
        } catch (\Exception $e) {
            Log::error("GuidesRelationManager: failed to notify guide [{$guide->id}]: " . $e->getMessage());
        }
    }
}
