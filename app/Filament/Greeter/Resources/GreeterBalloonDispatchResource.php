<?php

namespace App\Filament\Greeter\Resources;

use App\Models\BalloonDispatch;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Greeter\Resources\GreeterBalloonDispatchResource\Pages\ListGreeterBalloonDispatches;
use App\Filament\Greeter\Resources\GreeterBalloonDispatchResource\Pages\ViewGreeterBalloonDispatch;

class GreeterBalloonDispatchResource extends Resource
{
    protected static ?string $model = BalloonDispatch::class;

    protected static ?string $slug = 'balloon-dispatches';

    protected static ?string $recordTitleAttribute = 'dispatch_date';

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

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Balloon Dispatch';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    // ── No create / edit / delete ─────────────────────────────────────────────

    public static function canCreate(): bool   { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    // ── Query ─────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['creator'])
            ->withoutGlobalScopes();
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('dispatch_date', 'desc')
            ->columns([
                TextColumn::make('dispatch_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('notes_preview')
                    ->label('Operational Notes')
                    ->getStateUsing(fn (BalloonDispatch $record): string => $record->getContentExcerpt(120))
                    ->wrap(),

                IconColumn::make('has_image')
                    ->label('Image')
                    ->boolean()
                    ->getStateUsing(fn (BalloonDispatch $record): bool => $record->hasImage()),

                TextColumn::make('creator.name')
                    ->label('Posted By')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Posted At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('date_range')
                    ->label('Period')
                    ->options([
                        'today'   => 'Today',
                        'week'    => 'Last 7 Days',
                        'month'   => 'This Month',
                        'all'     => 'All',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'all') {
                            'today' => $query->whereDate('dispatch_date', today()),
                            'week'  => $query->whereBetween('dispatch_date', [today()->subDays(7), today()]),
                            'month' => $query->whereYear('dispatch_date', now()->year)
                                            ->whereMonth('dispatch_date', now()->month),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (BalloonDispatch $record): string => ViewGreeterBalloonDispatch::getUrl(['record' => $record])),
            ])
            ->bulkActions([])
            ->striped();
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListGreeterBalloonDispatches::route('/'),
            'view'  => ViewGreeterBalloonDispatch::route('/{record}'),
        ];
    }
}
