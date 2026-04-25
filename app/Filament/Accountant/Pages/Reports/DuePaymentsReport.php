<?php

namespace App\Filament\Accountant\Pages\Reports;

use App\Exports\DuePaymentsExport;
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
use Maatwebsite\Excel\Facades\Excel;

class DuePaymentsReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-exclamation-circle';
    }

    protected static ?int    $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Financial Reports';
    }

    public static function getNavigationLabel(): string
    {
        return 'Due Payments';
    }

    public function getTitle(): string
    {
        return 'Due Payments Report';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant', 'manager']) ?? false;
    }

    public function getView(): string
    {
        return 'filament.accountant.pages.reports.due-payments-report';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Accountant\Pages\Reports\Widgets\DuePaymentsStatsWidget::class,
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
                    $filters = $this->tableFilters ?? [];
                    return Excel::download(
                        new DuePaymentsExport([
                            'type'       => $filters['type']['value'] ?? null,
                            'date_from'  => $filters['date_range']['date_from'] ?? null,
                            'date_until' => $filters['date_range']['date_until'] ?? null,
                        ]),
                        'due-payments-' . now()->format('Y-m-d') . '.csv',
                        \Maatwebsite\Excel\Excel::CSV
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
                    ->where('balance_due', '>', 0)
                    ->whereIn('booking_status', ['confirmed', 'pending'])
                    ->orderByDesc('balance_due')
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
                    ->label('Partner / Customer')
                    ->default('—')
                    ->searchable()
                    ->limit(24),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(22),

                TextColumn::make('flight_date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('pax')
                    ->label('PAX')
                    ->state(fn($record) => ($record->adult_pax + $record->child_pax)),

                TextColumn::make('final_amount')
                    ->label('Total')
                    ->money('MAD')
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('MAD')
                    ->color('success'),

                TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->money('MAD')
                    ->color('danger')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'due'     => 'danger',
                        'on_site' => 'info',
                        default   => 'gray',
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
                    }),

                SelectFilter::make('type')
                    ->label('Booking Type')
                    ->options(['regular' => 'Regular', 'partner' => 'Partner']),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->defaultSort('balance_due', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}

