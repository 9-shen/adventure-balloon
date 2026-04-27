<?php

namespace App\Filament\Manager\Resources;

use App\Models\Booking;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Manager\Resources\BookingsReportResource\Pages;

class BookingsReportResource extends Resource
{
    protected static ?string $model = Booking::class;
    
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Bookings Reports';
    }
    
    public static function getModelLabel(): string
    {
        return 'Bookings Report';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Bookings Reports';
    }
    
    protected static ?int $navigationSort = 1;
    
    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('partner_display')
                    ->label('Partner / Type')
                    ->getStateUsing(fn (Booking $record): string => $record->type === 'partner' && $record->partner
                        ? $record->partner->company_name ?? $record->partner->name ?? 'Partner'
                        : '🔵 Regular')
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('type', 'like', "%{$search}%")
                        ->orWhereHas('partner', fn ($q) => $q->where('company_name', 'like', "%{$search}%"))
                    )
                    ->sortable(),

                TextColumn::make('pax_summary')
                    ->label('PAX')
                    ->getStateUsing(fn (Booking $record) => $record->adult_pax . ' Adult(s)' . ($record->child_pax > 0 ? ', ' . $record->child_pax . ' Child(ren)' : ''))
                    ->description(fn (Booking $record) => $record->getPaxAttendanceLabel()),

                TextColumn::make('final_amount')
                    ->label('Final Amount')
                    ->money('MAD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->money('MAD')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'due'     => 'danger',
                        'partial' => 'warning',
                        'on_site' => 'info',
                        'paid'    => 'success',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options([
                        'regular' => 'Regular',
                        'partner' => 'Partner',
                    ]),

                SelectFilter::make('partner_id')
                    ->label('Partner Name')
                    ->relationship('partner', 'company_name')
                    ->searchable()
                    ->preload(),

                Filter::make('flight_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('flight_from')
                            ->label('Flight Date From')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('flight_until')
                            ->label('Flight Date Until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['flight_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('flight_date', '>=', $date),
                            )
                            ->when(
                                $data['flight_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('flight_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['flight_from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Flight from: ' . \Carbon\Carbon::parse($data['flight_from'])->toFormattedDateString())
                                ->removeField('flight_from');
                        }
                        if ($data['flight_until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Flight until: ' . \Carbon\Carbon::parse($data['flight_until'])->toFormattedDateString())
                                ->removeField('flight_until');
                        }
                        return $indicators;
                    }),

                SelectFilter::make('payment_status')
                    ->options([
                        'due' => 'Due',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'on_site' => 'On Site',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'online' => 'Online',
                        'wire' => 'Wire Transfer',
                        'l_c'     => 'L.C',
                        'voucher' => 'Voucher',
                    ]),
                Filter::make('outstanding_balance')
                    ->label('Has Outstanding Balance')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('balance_due', '>', 0)),
            ])
            ->actions([])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('export_csv')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $query = \App\Models\Booking::query()->whereIn('id', $records->pluck('id'));
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\FinanceReportQueryExport($query),
                                'bookings_reports_selected.csv',
                                \Maatwebsite\Excel\Excel::CSV
                            );
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingsReports::route('/'),
        ];
    }
}
