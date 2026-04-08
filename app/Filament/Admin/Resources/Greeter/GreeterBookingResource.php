<?php

namespace App\Filament\Admin\Resources\Greeter;

use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Resources\Greeter\Pages\ListGreeterBookings;
use App\Filament\Admin\Resources\Greeter\Pages\ViewGreeterBooking;

class GreeterBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $slug = 'greeter/bookings';

    protected static ?string $recordTitleAttribute = 'booking_ref';

    public static function getNavigationLabel(): string
    {
        return "Today's Bookings";
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-identification';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Greeter';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'greeter']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('customers', 'product', 'partner')
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
            ->withoutGlobalScopes();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('flight_date', 'asc')
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->badge()
                    ->copyable()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'partner' ? 'purple' : 'info')
                    ->formatStateUsing(fn (string $state): string => $state === 'partner' ? '🤝 Partner' : '✈️ Regular'),

                TextColumn::make('flight_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('flight_time')
                    ->label('Time')
                    ->time('H:i')
                    ->placeholder('—'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(25),

                TextColumn::make('adult_pax')
                    ->label('Adults')
                    ->alignCenter(),

                TextColumn::make('child_pax')
                    ->label('Children')
                    ->alignCenter(),

                TextColumn::make('partner.company_name')
                    ->label('Partner')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default     => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                // PAX-level attendance summary — e.g. "✅ 2/4 Showed"
                TextColumn::make('pax_attendance')
                    ->label('PAX Attendance')
                    ->getStateUsing(fn (Booking $record): string => $record->getPaxAttendanceLabel())
                    ->badge()
                    ->color(fn (Booking $record): string => match (true) {
                        $record->customers->isEmpty()                                     => 'gray',
                        $record->customers->where('attendance', 'show')->count() === $record->customers->count() => 'success',
                        $record->customers->where('attendance', 'pending')->count() === $record->customers->count() => 'gray',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('flight_date_range')
                    ->label('Period')
                    ->options([
                        'today' => 'Today Only',
                        'week'  => 'Next 7 Days',
                        'all'   => 'All Upcoming',
                    ])
                    ->default('today')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'today') {
                            'week'  => $query->whereBetween('flight_date', [today(), today()->addDays(7)]),
                            'all'   => $query->whereDate('flight_date', '>=', today()),
                            default => $query->whereDate('flight_date', today()),
                        };
                    }),

                SelectFilter::make('booking_status')
                    ->label('Booking Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                    ])
                    ->native(false),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Booking $record): string => ViewGreeterBooking::getUrl(['record' => $record]))
                    ->label('Manage Attendance')
                    ->icon('heroicon-o-user-group'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\Greeter\RelationManagers\GreeterCustomersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGreeterBookings::route('/'),
            'view'  => ViewGreeterBooking::route('/{record}'),
        ];
    }
}
