<?php

namespace App\Filament\Sales\Resources\Bookings;

use App\Filament\Sales\Resources\Bookings\Pages\CreateSalesBooking;
use App\Filament\Sales\Resources\Bookings\Pages\ListSalesBookings;
use App\Filament\Sales\Resources\Bookings\Pages\ViewSalesBooking;
use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SalesBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    public static function getNavigationIcon(): string|\BackedEnum|null { return 'heroicon-o-calendar-days'; }
    public static function getNavigationLabel(): string { return 'My Bookings'; }
    public static function getNavigationGroup(): ?string { return 'My Bookings'; }
    public static function getNavigationSort(): ?int { return 1; }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('sales') ?? false;
    }

    /** Scope to bookings created by this sales representative only */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('created_by', Auth::id())
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

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'on_site' => 'info',
                        default   => 'danger',
                    }),

                TextColumn::make('final_amount')
                    ->label('Total')
                    ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency()),
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
            'index'  => ListSalesBookings::route('/'),
            'create' => CreateSalesBooking::route('/create'),
            'view'   => ViewSalesBooking::route('/{record}'),
        ];
    }
}
