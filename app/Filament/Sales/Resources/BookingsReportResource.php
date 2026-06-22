<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\BookingsReportResource\Pages\ListBookingsReports;
use App\Models\Booking;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class BookingsReportResource extends Resource
{
    protected static ?string $model = Booking::class;

    // ─── Navigation ───────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Booking Report';
    }

    public static function getModelLabel(): string
    {
        return 'Booking Report';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Booking Report';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'My Bookings';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    // ─── Access Control ───────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('sales') ?? false;
    }

    public static function canCreate(): bool        { return false; }
    public static function canEdit($record): bool   { return false; }
    public static function canDelete($record): bool { return false; }

    // ─── Query Scope — this sales user's bookings only ─────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('created_by', Auth::id());
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Copied!'),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pax_summary')
                    ->label('PAX')
                    ->getStateUsing(fn (Booking $record): string =>
                        $record->adult_pax . ' Adult(s)' .
                        ($record->child_pax > 0 ? ', ' . $record->child_pax . ' Child(ren)' : ''))
                    ->description(fn (Booking $record): string => $record->getPaxAttendanceLabel()),

                TextColumn::make('final_amount')
                    ->label('Total')
                    ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency())
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency())
                    ->sortable(),

                TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->money(fn () => app(\App\Settings\AppSettings::class)->getIsoCurrency())
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
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed'  => 'success',
                        'cancelled'  => 'danger',
                        'completed'  => 'info',
                        default      => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('booking_status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->options([
                        'unpaid'  => 'Unpaid',
                        'partial' => 'Partial',
                        'paid'    => 'Paid',
                        'on_site' => 'On Site',
                    ]),

                Filter::make('flight_date')
                    ->form([
                        DatePicker::make('flight_from')
                            ->label('Flight Date From')
                            ->native(false),
                        DatePicker::make('flight_until')
                            ->label('Flight Date Until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['flight_from'],
                                fn (Builder $q, $date) => $q->whereDate('flight_date', '>=', $date),
                            )
                            ->when(
                                $data['flight_until'],
                                fn (Builder $q, $date) => $q->whereDate('flight_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['flight_from'] ?? null) {
                            $indicators[] = Indicator::make('From: ' . \Carbon\Carbon::parse($data['flight_from'])->toFormattedDateString())
                                ->removeField('flight_from');
                        }
                        if ($data['flight_until'] ?? null) {
                            $indicators[] = Indicator::make('Until: ' . \Carbon\Carbon::parse($data['flight_until'])->toFormattedDateString())
                                ->removeField('flight_until');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('export_selected')
                        ->label('Export Selected (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $query = Booking::query()->whereIn('id', $records->pluck('id'));
                            return Excel::download(
                                new \App\Exports\SalesBookingsExport($query),
                                'my_bookings_selected.csv',
                                \Maatwebsite\Excel\Excel::CSV
                            );
                        }),
                ]),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped();
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListBookingsReports::route('/'),
        ];
    }
}
