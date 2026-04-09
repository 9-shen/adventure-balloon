<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Exports\RevenueReportExport;
use App\Models\Booking;
use App\Models\Product;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RevenueReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Financial Reports';
    }

    public static function getNavigationLabel(): string
    {
        return 'Revenue Report';
    }

    public function getTitle(): string
    {
        return 'Revenue Report';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant', 'manager']) ?? false;
    }

    public function getView(): string
    {
        return 'filament.admin.pages.reports.revenue-report';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Pages\Reports\Widgets\RevenueStatsWidget::class,
        ];
    }

    private function getBaseQuery(): Builder
    {
        return Booking::query()
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed']);
    }

    // -------------------------------------------------------
    // Header actions — Export ALL data
    // -------------------------------------------------------
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_all')
                ->label('Export All')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filters = $this->getTableFiltersForm()->getState();
                    return Excel::download(
                        new RevenueReportExport([
                            'date_from'      => $filters['date_range']['date_from'] ?? null,
                            'date_until'     => $filters['date_range']['date_until'] ?? null,
                            'type'           => $filters['type']['value'] ?? null,
                            'product_id'     => $filters['product']['value'] ?? null,
                            'payment_status' => $filters['payment_status']['value'] ?? null,
                            'booking_status' => $filters['booking_status']['value'] ?? null,
                        ]),
                        'revenue-all-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }

    // -------------------------------------------------------
    // Table — with checkboxes + bulk export selected
    // -------------------------------------------------------
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn() => Booking::query()
                    ->with(['partner', 'product'])
                    ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
                    ->orderByDesc('flight_date')
            )
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Ref')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn($state) => $state === 'partner' ? 'info' : 'gray')
                    ->formatStateUsing(fn($state) => strtoupper($state)),

                TextColumn::make('partner.company_name')
                    ->label('Partner / Source')
                    ->default(fn($record) => $record->booking_source ?? '—')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(22),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('pax')
                    ->label('PAX')
                    ->state(fn($record) => ($record->adult_pax + $record->child_pax))
                    ->alignCenter(),

                TextColumn::make('final_amount')
                    ->label('Total')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('MAD')
                    ->color('success'),

                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('MAD')
                    ->color(fn($state) => (float)$state > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'due'     => 'danger',
                        'on_site' => 'info',
                        default   => 'gray',
                    }),

                TextColumn::make('booking_status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'confirmed' => 'success',
                        'pending'   => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default     => 'gray',
                    }),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) $indicators[] = 'From: ' . $data['date_from'];
                        if ($data['date_until'] ?? null) $indicators[] = 'Until: ' . $data['date_until'];
                        return $indicators;
                    }),

                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options(['regular' => 'Regular', 'partner' => 'Partner']),

                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_status')
                    ->options(['paid' => 'Paid', 'partial' => 'Partial', 'due' => 'Due', 'on_site' => 'On-Site']),

                SelectFilter::make('booking_status')
                    ->options(['confirmed' => 'Confirmed', 'pending' => 'Pending', 'completed' => 'Completed']),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->bulkActions([
                \Filament\Actions\BulkAction::make('export_selected')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->toArray();
                        return Excel::download(
                            new RevenueReportExport(['ids' => $ids]),
                            'revenue-selected-' . now()->format('Y-m-d') . '.xlsx'
                        );
                    }),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
