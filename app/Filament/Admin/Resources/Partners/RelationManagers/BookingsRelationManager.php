<?php

namespace App\Filament\Admin\Resources\Partners\RelationManagers;

use App\Models\Booking;
use App\Models\Guide;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Bookings';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar-days';

    // ─── Infolist (not used — view handled inline) ────────────────────────────

    public function isReadOnly(): bool
    {
        return true;
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->where('type', 'partner')
                ->with(['product', 'guide', 'customers']))
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('partner_reference')
                    ->label('Your Ref')
                    ->placeholder('—')
                    ->searchable()
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

                TextColumn::make('guide.name')
                    ->label('Guide')
                    ->placeholder('Not assigned')
                    ->searchable(),

                TextColumn::make('pax_summary')
                    ->label('PAX')
                    ->getStateUsing(fn (Booking $record): string =>
                        $record->adult_pax . ' Adult(s)' .
                        ($record->child_pax > 0 ? ', ' . $record->child_pax . ' Child(ren)' : ''))
                    ->description(fn (Booking $record): string => $record->getPaxAttendanceLabel()),

                TextColumn::make('pickup_location')
                    ->label('Pickup')
                    ->limit(25)
                    ->placeholder('Not set')
                    ->toggleable(),

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

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
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

                SelectFilter::make('guide_id')
                    ->label('Guide')
                    ->options(fn () => Guide::where('partner_id', $this->getOwnerRecord()?->id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                    ),

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
            ->toolbarActions([
                // Header: export all filtered rows
                \Filament\Actions\Action::make('export_all')
                    ->label('Export Bookings (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $query = $this->getFilteredTableQuery();
                        return Excel::download(
                            new \App\Exports\PartnerBookingsExport($query),
                            'partner_bookings_export.csv',
                            \Maatwebsite\Excel\Excel::CSV
                        );
                    }),

                // Bulk: export selected rows
                BulkActionGroup::make([
                    BulkAction::make('export_selected')
                        ->label('Export Selected (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $query = Booking::query()->whereIn('id', $records->pluck('id'));
                            return Excel::download(
                                new \App\Exports\PartnerBookingsExport($query),
                                'partner_bookings_selected.csv',
                                \Maatwebsite\Excel\Excel::CSV
                            );
                        }),
                ]),
            ])
            ->recordAction(null)
            ->defaultSort('flight_date', 'desc');
    }
}
