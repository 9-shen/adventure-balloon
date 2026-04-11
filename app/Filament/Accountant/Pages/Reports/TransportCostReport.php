<?php

namespace App\Filament\Accountant\Pages\Reports;

use App\Exports\TransportCostExport;
use App\Models\Dispatch;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TransportCostReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    protected static ?string $navigationLabel = 'Transport Cost Report';
    protected static ?string $title           = 'Transport Cost Report';
    protected static ?string $slug            = 'transport-cost-report';
    protected static ?int $navigationSort     = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'Financial Reports';
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'accountant', 'manager']) ?? false;
    }

    public function getView(): string
    {
        return 'filament.admin.pages.reports.transport-cost-report';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Accountant\Pages\Reports\Widgets\TransportCostStatsWidget::class,
        ];
    }

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
                        new TransportCostExport([
                            'transport_company_id' => $filters['transport_company_id']['value'] ?? null,
                            'date_from'  => $filters['date_range']['from'] ?? null,
                            'date_until' => $filters['date_range']['until'] ?? null,
                            'status'     => $filters['status']['value'] ?? null,
                            'billed'     => isset($filters['not_billed']) && $filters['not_billed'] ? 'no' : null,
                        ]),
                        'transport-costs-' . now()->format('Y-m-d') . '.csv',
                        \Maatwebsite\Excel\Excel::CSV
                    );
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Dispatch::query()
                    ->with(['transportCompany', 'booking'])
                    ->withCount('dispatchDriverRows as vehicles_count')
            )
            ->columns([
                TextColumn::make('dispatch_ref')
                    ->label('Dispatch Ref')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('booking.booking_ref')
                    ->label('Booking')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('transportCompany.company_name')
                    ->label('Transporter')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_pax')
                    ->label('PAX')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('vehicles_count')
                    ->label('Vehicles')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('transport_cost')
                    ->label('Cost (MAD)')
                    ->money('MAD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed'   => 'success',
                        'in_progress' => 'info',
                        'delivered'   => 'primary',
                        'cancelled'   => 'danger',
                        default       => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                IconColumn::make('billed_at')
                    ->label('Billed')
                    ->boolean()
                    ->getStateUsing(fn (Dispatch $d) => $d->isBilled())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('transport_company_id')
                    ->label('Transport Company')
                    ->relationship('transportCompany', 'company_name')
                    ->searchable()
                    ->preload(),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Until')->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q, $v) => $q->whereDate('flight_date', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('flight_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'])  $indicators[] = Tables\Filters\Indicator::make('From: ' . $data['from']);
                        if ($data['until']) $indicators[] = Tables\Filters\Indicator::make('Until: ' . $data['until']);
                        return $indicators;
                    }),

                SelectFilter::make('status')
                    ->label('Dispatch Status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'delivered'   => 'Delivered',
                        'cancelled'   => 'Cancelled',
                    ]),

                Filter::make('not_billed')
                    ->label('Unbilled Only')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->whereNull('billed_at')),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped();
    }
}

