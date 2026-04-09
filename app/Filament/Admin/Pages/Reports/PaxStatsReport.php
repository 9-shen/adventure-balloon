<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Exports\PaxStatsExport;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PaxStatsReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    protected static ?int    $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Financial Reports';
    }

    public static function getNavigationLabel(): string
    {
        return 'PAX Statistics';
    }

    public function getTitle(): string
    {
        return 'PAX & Flight Statistics';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant', 'manager']) ?? false;
    }

    public function getView(): string
    {
        return 'filament.admin.pages.reports.pax-stats-report';
    }

    /**
     * Aggregate queries have no 'id' — use composite key flight_date+type
     */
    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string
    {
        if (is_array($record)) {
            return ($record['flight_date'] ?? '') . '_' . ($record['type'] ?? 'all');
        }
        return $record->flight_date . '_' . ($record->type ?? 'all');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Pages\Reports\Widgets\PaxStatsWidget::class,
        ];
    }

    // -------------------------------------------------------
    // Export
    // -------------------------------------------------------
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filters = $this->getTableFiltersForm()->getState();
                    return Excel::download(
                        new PaxStatsExport([
                            'date_from'  => $filters['date_range']['date_from'] ?? null,
                            'date_until' => $filters['date_range']['date_until'] ?? null,
                            'type'       => $filters['type']['value'] ?? null,
                        ]),
                        'pax-stats-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }

    // -------------------------------------------------------
    // Table — grouped by flight_date
    // -------------------------------------------------------
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn() => Booking::query()
                    ->select([
                        'flight_date',
                        'type',
                        DB::raw('COUNT(*) as total_flights'),
                        DB::raw('SUM(adult_pax + child_pax) as total_pax'),
                        DB::raw('SUM(adult_pax) as total_adults'),
                        DB::raw('SUM(child_pax) as total_children'),
                        DB::raw('SUM(CASE WHEN attendance = "show" THEN adult_pax + child_pax ELSE 0 END) as showed'),
                        DB::raw('SUM(CASE WHEN attendance = "no_show" THEN adult_pax + child_pax ELSE 0 END) as no_showed'),
                    ])
                    ->whereIn('booking_status', ['confirmed', 'completed'])
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
                    ->badge()
                    ->color(fn($state) => $state === 'partner' ? 'info' : 'gray')
                    ->formatStateUsing(fn($state) => strtoupper($state ?? '—')),

                TextColumn::make('total_flights')
                    ->label('Flights')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                TextColumn::make('total_pax')
                    ->label('Total PAX')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('total_adults')
                    ->label('Adults')
                    ->alignCenter(),

                TextColumn::make('total_children')
                    ->label('Children')
                    ->alignCenter(),

                TextColumn::make('showed')
                    ->label('Showed')
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('no_showed')
                    ->label('No-Show')
                    ->color('danger')
                    ->alignCenter(),

                TextColumn::make('no_show_rate')
                    ->label('No-Show Rate')
                    ->state(function ($record): string {
                        if (!$record->total_pax) return '—';
                        return round(($record->no_showed / $record->total_pax) * 100, 1) . '%';
                    })
                    ->color(fn($state) => (float)$state > 20 ? 'danger' : 'success')
                    ->alignCenter(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')->label('From Date')->native(false),
                        DatePicker::make('date_until')->label('Until Date')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['date_from'], fn($q, $v) => $q->whereDate('flight_date', '>=', $v))
                            ->when($data['date_until'], fn($q, $v) => $q->whereDate('flight_date', '<=', $v));
                    }),

                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options(['regular' => 'Regular', 'partner' => 'Partner']),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
