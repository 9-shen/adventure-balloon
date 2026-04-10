<?php

namespace App\Filament\Partner\Resources\Bookings;

use App\Filament\Partner\Resources\Bookings\Pages\CreatePartnerBooking;
use App\Filament\Partner\Resources\Bookings\Pages\ListPartnerBookings;
use App\Filament\Partner\Resources\Bookings\Pages\ViewPartnerBooking;
use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PartnerBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Bookings';
    }

    public static function getModelLabel(): string
    {
        return 'Booking';
    }

    public static function getPluralModelLabel(): string
    {
        return 'My Bookings';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'My Bookings';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    /**
     * Scope all queries to the logged-in partner's bookings only.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->where('partner_id', $user->partner_id)
            ->where('type', 'partner')
            ->latest('flight_date');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('product.name')
                    ->label('Experience')
                    ->searchable()
                    ->sortable(),

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

                TextColumn::make('final_amount')
                    ->label('Amount')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger'  => 'due',
                    ]),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'confirmed',
                        'warning' => 'pending',
                        'danger'  => 'cancelled',
                    ]),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPartnerBookings::route('/'),
            'create' => CreatePartnerBooking::route('/create'),
            'view'   => ViewPartnerBooking::route('/{record}'),
        ];
    }
}
