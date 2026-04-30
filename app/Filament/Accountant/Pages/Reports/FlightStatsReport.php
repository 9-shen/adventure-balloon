<?php

namespace App\Filament\Accountant\Pages\Reports;

use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FlightStatsExport;

class FlightStatsReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-paper-airplane';
    }

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'Financial Reports';
    }

    public static function getNavigationLabel(): string
    {
        return 'Flight Stats';
    }

    public function getTitle(): string
    {
        return 'Flight Statistics';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function getView(): string
    {
        return 'filament.accountant.pages.reports.flight-stats-report';
    }

    // ── Header actions ─────────────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filters = $this->tableFilters ?? [];
                    return Excel::download(
                        new FlightStatsExport([
                            'date_from'  => $filters['date_range']['date_from'] ?? null,
                            'date_until' => $filters['date_range']['date_until'] ?? null,
                            'type'       => $filters['type']['value'] ?? null,
                        ]),
                        'flight-stats-' . now()->format('Y-m-d') . '.csv',
                        \Maatwebsite\Excel\Excel::CSV
                    );
                }),
        ];
    }

    // ── Table ──────────────────────────────────────────────────────────────────
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Booking::query()
                    ->select([
                        // MIN(id) avoids ONLY_FULL_GROUP_BY issues in strict MySQL mode
                        DB::raw('MIN(id) as id'),
                        'flight_date',
                        'type',
                        DB::raw('COUNT(*) as total_bookings'),
                        DB::raw('SUM(adult_pax + child_pax) as total_pax'),
                        DB::raw('SUM(adult_pax) as total_adults'),
                        DB::raw('SUM(child_pax) as total_children'),
                    ])
                    ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
                    ->groupBy('flight_date', 'type')
                    ->orderByDesc('flight_date')
            )
            ->columns([
                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state): string => $state === 'partner' ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state): string => strtoupper($state ?? '—')),

                TextColumn::make('total_bookings')
                    ->label('Total Bookings')
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_pax')
                    ->label('Total PAX')
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_adults')
                    ->label('Adults')
                    ->alignCenter()
                    ->color('info'),

                TextColumn::make('total_children')
                    ->label('Children')
                    ->alignCenter()
                    ->color('success'),

            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->native(false),
                        DatePicker::make('date_until')
                            ->label('Until Date')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $query
                            ->when($data['date_from'],  fn ($q, $v) => $q->whereDate('flight_date', '>=', $v))
                            ->when($data['date_until'], fn ($q, $v) => $q->whereDate('flight_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from']  ?? null) $indicators[] = 'From: '  . $data['date_from'];
                        if ($data['date_until'] ?? null) $indicators[] = 'Until: ' . $data['date_until'];
                        return $indicators;
                    }),

                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options(['regular' => 'Regular', 'partner' => 'Partner']),
            ])
            ->bulkActions([
                BulkAction::make('export_selected')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (BulkAction $action, \Livewire\Component $livewire) {
                        // Aggregate rows have composite keys — read raw selected keys from Livewire
                        $groups = $livewire->selectedTableRecords ?? [];
                        return Excel::download(
                            new FlightStatsExport(['groups' => $groups]),
                            'flight-stats-selected-' . now()->format('Y-m-d') . '.csv',
                            \Maatwebsite\Excel\Excel::CSV
                        );
                    }),
            ])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Aggregate queries need a deterministic key (flight_date + type).
     */
    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string
    {
        if (is_array($record)) {
            return ($record['flight_date'] ?? '') . '_' . ($record['type'] ?? 'all');
        }
        return $record->flight_date . '_' . ($record->type ?? 'all');
    }
}
