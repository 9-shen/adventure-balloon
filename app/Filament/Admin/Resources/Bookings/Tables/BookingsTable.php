<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use App\Models\Partner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                // ── Booking type badge (regular = blue, partner = purple) ──
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'partner' => 'purple',
                        default   => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                // ── Partner name (toggleable, blank for regular bookings) ──
                TextColumn::make('partner.company_name')
                    ->label('Partner')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

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
                    ->label('Total (MAD)')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'on_site' => 'info',
                        default   => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid'    => 'Paid',
                        'partial' => 'Partial',
                        'on_site' => 'On-Site',
                        default   => 'Due',
                    }),

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

                TextColumn::make('booking_source')
                    ->label('Source')
                    ->toggleable()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '—')),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('flight_date', 'desc')
            ->filters([
                // ── Booking type filter ──
                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options([
                        'regular' => 'Regular',
                        'partner' => 'Partner',
                    ]),

                // ── Partner filter ──
                SelectFilter::make('partner_id')
                    ->label('Partner')
                    ->options(fn () => Partner::where('is_active', true)
                        ->orderBy('company_name')
                        ->pluck('company_name', 'id'))
                    ->searchable(),

                SelectFilter::make('booking_status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'due'     => 'Due',
                        'partial' => 'Partial',
                        'paid'    => 'Paid',
                        'on_site' => 'On-Site',
                    ]),

                SelectFilter::make('booking_source')
                    ->label('Source')
                    ->options([
                        'walk-in'  => 'Walk-In',
                        'phone'    => 'Phone',
                        'website'  => 'Website',
                        'email'    => 'Email',
                        'referral' => 'Referral',
                        'other'    => 'Other',
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
