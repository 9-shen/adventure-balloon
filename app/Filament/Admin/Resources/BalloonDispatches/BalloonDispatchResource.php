<?php

namespace App\Filament\Admin\Resources\BalloonDispatches;

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
use App\Filament\Admin\Resources\BalloonDispatches\Pages;

class BalloonDispatchResource extends Resource
{
    protected static ?string $model = BalloonDispatch::class;

    protected static ?string $slug = 'balloon-dispatches';

    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function getNavigationLabel(): string
    {
        return 'Balloon Dispatches';
    }

    public static function getModelLabel(): string
    {
        return 'Balloon Dispatch';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Balloon Dispatches';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Balloon Dispatch';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(2)
            ->components([
                Section::make('Dispatch Details')
                    ->description('Fill in the balloon dispatch information for the day.')
                    ->columnSpan(1)
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
                                'redo'
                            ])
                    ]),

                Section::make('Attach Image')
                    ->description('Upload an optional photo for the dispatch.')
                    ->columnSpan(1)
                    ->components([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->hiddenLabel()
                            ->collection('balloon-dispatch-images')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->helperText('Optional. Upload one image (JPEG, PNG, WEBP — max 5MB).'),
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
                    ->getStateUsing(fn(BalloonDispatch $record): string => $record->getContentExcerpt(100))
                    ->wrap(),

                IconColumn::make('has_image')
                    ->label('Image')
                    ->boolean()
                    ->getStateUsing(fn(BalloonDispatch $record): bool => $record->hasImage()),

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
                ViewAction::make(),
                EditAction::make(),
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
            'index'  => Pages\ListBalloonDispatches::route('/'),
            'create' => Pages\CreateBalloonDispatch::route('/create'),
            'view'   => Pages\ViewBalloonDispatch::route('/{record}'),
            'edit'   => Pages\EditBalloonDispatch::route('/{record}/edit'),
        ];
    }
}
