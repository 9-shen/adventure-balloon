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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RevenueReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    protected static ?int    $navigationSort  = 1;

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

    // -------------------------------------------------------
    // Stats helpers (used in the blade view)
    // -------------------------------------------------------
    public function getTotalRevenue(): string
    {
        return number_format((float) $this->getBaseQuery()->sum('final_amount'), 2);
    }

    public function getTotalCollected(): string
    {
        return number_format((float) $this->getBaseQuery()->sum('amount_paid'), 2);
    }

    public function getTotalOutstanding(): string
    {
        return number_format((float) $this->getBaseQuery()->sum('balance_due'), 2);
    }

    public function getTotalBookings(): int
    {
        return $this->getBaseQuery()->count();
    }

    private function getBaseQuery(): Builder
    {
        return Booking::query()
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed']);
    }

    // -------------------------------------------------------
    // Export action
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
                        new RevenueReportExport([
                            'date_from'      => $filters['date_range']['date_from'] ?? null,
                            'date_until'     => $filters['date_range']['date_until'] ?? null,
                            'type'           => $filters['type']['value'] ?? null,
                            'product_id'     => $filters['product']['value'] ?? null,
                            'payment_status' => $filters['payment_status']['value'] ?? null,
                            'booking_status' => $filters['booking_status']['value'] ?? null,
                        ]),
                        'revenue-report-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }

    // -------------------------------------------------------
    // Table
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
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('pax')
                    ->label('PAX')
                    ->state(fn($record) => ($record->adult_pax + $record->child_pax) . 'A+' . $record->child_pax . 'C')
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
                        'paid'     => 'success',
                        'partial'  => 'warning',
                        'due'      => 'danger',
                        'on_site'  => 'info',
                        default    => 'gray',
                    }),

                TextColumn::make('booking_status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'confirmed'  => 'success',
                        'pending'    => 'warning',
                        'cancelled'  => 'danger',
                        'completed'  => 'info',
                        default      => 'gray',
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
            ->defaultSort('flight_date', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
