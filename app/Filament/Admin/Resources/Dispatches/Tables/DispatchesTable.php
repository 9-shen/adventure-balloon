<?php

namespace App\Filament\Admin\Resources\Dispatches\Tables;

use App\Models\TransportCompany;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DispatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dispatch_ref')
                    ->label('Ref')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('booking.booking_ref')
                    ->label('Booking')
                    ->badge()
                    ->color(fn ($record) => $record->booking?->type === 'partner' ? 'purple' : 'info')
                    ->searchable(),

                TextColumn::make('transportCompany.company_name')
                    ->label('Transport Company')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('pickup_time')
                    ->label('Pickup')
                    ->time('H:i'),

                TextColumn::make('total_pax')
                    ->label('PAX')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed'   => 'success',
                        'in_progress' => 'warning',
                        'delivered'   => 'info',
                        'cancelled'   => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_progress' => 'In Progress',
                        default       => ucfirst($state),
                    }),

                TextColumn::make('notified_at')
                    ->label('Notified')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Not sent')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'delivered'   => 'Delivered',
                        'cancelled'   => 'Cancelled',
                    ]),

                SelectFilter::make('transport_company_id')
                    ->label('Transport Company')
                    ->options(TransportCompany::where('is_active', true)->pluck('company_name', 'id'))
                    ->searchable(),
            ])
            ->recordAction('view')
            ->defaultSort('flight_date', 'desc');
    }
}
