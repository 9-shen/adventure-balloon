<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Exports\PartnerSummaryExport;
use App\Models\Partner;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class PartnerSummaryReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-office';
    }

    protected static ?int    $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Financial Reports';
    }

    public static function getNavigationLabel(): string
    {
        return 'Partner Summary';
    }

    public function getTitle(): string
    {
        return 'Partner Booking Summary';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant', 'manager']) ?? false;
    }
    public function getView(): string
    {
        return 'filament.admin.pages.reports.partner-summary-report';
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
                    $filters = $this->tableFilters ?? [];
                    return Excel::download(
                        new PartnerSummaryExport([
                            'date_from'  => $filters['date_range']['date_from'] ?? null,
                            'date_until' => $filters['date_range']['date_until'] ?? null,
                        ]),
                        'partner-summary-' . now()->format('Y-m-d') . '.csv',
                        \Maatwebsite\Excel\Excel::CSV
                    );
                }),
        ];
    }

    // -------------------------------------------------------
    // Table — aggregate per partner via sub-queries
    // -------------------------------------------------------
    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => Partner::query()
                ->withCount(['bookings as total_bookings' => function ($q) {
                    $q->whereIn('booking_status', ['confirmed', 'pending', 'completed']);
                }])
                ->withSum(['bookings as total_revenue' => function ($q) {
                    $q->whereIn('booking_status', ['confirmed', 'pending', 'completed']);
                }], 'final_amount')
                ->withSum(['bookings as total_paid' => function ($q) {
                    $q->whereIn('booking_status', ['confirmed', 'pending', 'completed']);
                }], 'amount_paid')
                ->withSum(['bookings as total_outstanding' => function ($q) {
                    $q->whereIn('booking_status', ['confirmed', 'pending', 'completed']);
                }], 'balance_due')
                ->withCount('invoices as invoices_count')
                ->orderByDesc('total_revenue')
            )
            ->columns([
                TextColumn::make('company_name')
                    ->label('Partner')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->searchable()
                    ->limit(24)
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'approved' => 'success',
                        'pending'  => 'warning',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('total_bookings')
                    ->label('Bookings')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->formatStateUsing(fn($state) => 'MAD ' . number_format((float)$state, 2))
                    ->sortable(),

                TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->formatStateUsing(fn($state) => 'MAD ' . number_format((float)$state, 2))
                    ->color('success'),

                TextColumn::make('total_outstanding')
                    ->label('Outstanding')
                    ->formatStateUsing(fn($state) => 'MAD ' . number_format((float)$state, 2))
                    ->color(fn($state) => (float)$state > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')->label('From Date')->native(false),
                        DatePicker::make('date_until')->label('Until Date')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        // Re-apply booking sub-query conditions if date range is set
                        if ($data['date_from'] ?? null) {
                            $query->whereHas('bookings', fn($q) => $q->whereDate('flight_date', '>=', $data['date_from']));
                        }
                        if ($data['date_until'] ?? null) {
                            $query->whereHas('bookings', fn($q) => $q->whereDate('flight_date', '<=', $data['date_until']));
                        }
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->striped()
            ->paginated([25, 50]);
    }
}
