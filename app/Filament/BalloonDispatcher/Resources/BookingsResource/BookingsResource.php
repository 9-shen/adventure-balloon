<?php

namespace App\Filament\BalloonDispatcher\Resources\BookingsResource;

use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsResource extends Resource
{
    protected static ?string $model = Booking::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationLabel(): string  { return 'All Bookings'; }
    public static function getModelLabel(): string       { return 'Booking'; }
    public static function getPluralModelLabel(): string { return 'Bookings'; }
    public static function getNavigationGroup(): ?string { return 'Bookings'; }
    public static function getNavigationSort(): ?int     { return 1; }

    public static function canCreate(): bool  { return false; }
    public static function canEdit($record = null): bool   { return false; }
    public static function canDelete($record = null): bool { return false; }

    public static function form(Schema $form): Schema
    {
        return $form->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->searchable()
                    ->copyable()
                    ->weight('semibold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'partner' => 'info',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('partner.company_name')
                    ->label('Partner')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_pax')
                    ->label('PAX')
                    ->getStateUsing(fn (Booking $record): int => $record->getTotalPax()),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'pending'   => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options([
                        'regular' => 'Regular Booking',
                        'partner' => 'Partner Booking',
                    ])
                    ->native(false),

                SelectFilter::make('booking_status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->native(false),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('flight_date', 'asc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBalloonDispatcherBookings::route('/'),
            'view'  => Pages\ViewBalloonDispatcherBooking::route('/{record}'),
        ];
    }
}
