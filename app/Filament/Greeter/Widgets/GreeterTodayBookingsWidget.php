<?php

namespace App\Filament\Greeter\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Greeter\Resources\GreeterBookingResource;

class GreeterTodayBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = "Today's Flight Schedule";

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with('customers', 'product')
                    ->whereDate('flight_date', today())
                    ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
                    ->withoutGlobalScopes()
                    ->orderBy('flight_time')
            )
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'partner' ? 'purple' : 'info')
                    ->formatStateUsing(fn (string $state): string => $state === 'partner' ? '🤝 Partner' : '✈️ Regular'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(20),

                TextColumn::make('flight_time')
                    ->label('Time')
                    ->time('H:i')
                    ->placeholder('—'),

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
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('pax_attendance')
                    ->label('Attendance')
                    ->getStateUsing(fn (Booking $record): string => $record->getPaxAttendanceLabel())
                    ->badge()
                    ->color(fn (Booking $record): string => match (true) {
                        $record->customers->isEmpty()                                                            => 'gray',
                        $record->customers->where('attendance', 'show')->count() === $record->customers->count() => 'success',
                        $record->customers->where('attendance', 'pending')->count() === $record->customers->count() => 'gray',
                        default => 'warning',
                    }),
            ])
            ->actions([
                Action::make('manage')
                    ->label('Manage Attendance')
                    ->icon('heroicon-o-user-group')
                    ->color('primary')
                    ->url(fn (Booking $record): string => GreeterBookingResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([10, 25])
            ->defaultPaginationPageOption(10);
    }
}
