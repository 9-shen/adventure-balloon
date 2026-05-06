<?php

namespace App\Filament\BalloonDispatcher\Resources\BalloonDispatchResource;

use App\Models\BalloonDispatch;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BalloonDispatchResource extends Resource
{
    protected static ?string $model = BalloonDispatch::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function getNavigationLabel(): string  { return 'Balloon Dispatch'; }
    public static function getModelLabel(): string       { return 'Balloon Dispatch'; }
    public static function getPluralModelLabel(): string { return 'Balloon Dispatches'; }
    public static function getNavigationGroup(): ?string { return 'Balloon Dispatch'; }
    public static function getNavigationSort(): ?int     { return 1; }

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Section::make('Dispatch Details')
                ->description('Fill in the balloon dispatch information for the day.')
                ->columns(1)
                ->components([
                    DatePicker::make('dispatch_date')
                        ->label('Dispatch Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    RichEditor::make('content')
                        ->label('Operational Notes')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'bulletList',
                            'orderedList',
                            'link',
                            'h2',
                            'h3',
                            'undo',
                            'redo',
                        ])
                        ->columnSpanFull(),

                    SpatieMediaLibraryFileUpload::make('image')
                        ->label('Attach Image')
                        ->collection('balloon-dispatch-images')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->helperText('Optional. Upload one image (JPEG, PNG, WEBP — max 5MB).')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dispatch_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('content_excerpt')
                    ->label('Notes Preview')
                    ->getStateUsing(fn (BalloonDispatch $record): string => $record->getContentExcerpt(100))
                    ->wrap(),

                IconColumn::make('has_image')
                    ->label('Image')
                    ->boolean()
                    ->getStateUsing(fn (BalloonDispatch $record): bool => $record->hasImage()),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('dispatch_date', 'desc')
            ->actions([
                ViewAction::make()
                    ->modalWidth('4xl'),

                EditAction::make()
                    ->modalWidth('4xl'),

                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            // Only the index page — all CRUD happens in modals
            'index' => Pages\ListBalloonDispatches::route('/'),
        ];
    }
}
