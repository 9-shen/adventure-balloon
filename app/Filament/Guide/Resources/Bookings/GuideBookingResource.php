<?php

namespace App\Filament\Guide\Resources\Bookings;

use App\Filament\Guide\Resources\Bookings\Pages\CreateGuideBooking;
use App\Filament\Guide\Resources\Bookings\Pages\ListGuideBookings;
use App\Filament\Guide\Resources\Bookings\Pages\ViewGuideBooking;
use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GuideBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-calendar-days'; }
    public static function getNavigationLabel(): string { return 'My Bookings'; }
    public static function getNavigationGroup(): ?string { return 'My Bookings'; }
    public static function getNavigationSort(): ?int { return 1; }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('guide') ?? false;
    }

    /** Scope to bookings created by this guide only */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('guide_id', Auth::user()->guide_id)
            ->latest('flight_date');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('product.name')
                    ->label('Experience')
                    ->searchable(),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('adult_pax')
                    ->label('Adults')
                    ->alignCenter(),

                TextColumn::make('child_pax')
                    ->label('Children')
                    ->alignCenter(),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default     => 'warning',
                    }),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped();
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGuideBookings::route('/'),
            'create' => CreateGuideBooking::route('/create'),
            'view'   => ViewGuideBooking::route('/{record}'),
        ];
    }
}
