<?php

namespace App\Filament\Transport\Pages;

use App\Filament\Transport\Widgets\TransportStatsWidget;
use App\Models\Dispatch;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DispatchStats extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.transport.pages.dispatch-stats';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dispatch Stats & Export';
    }

    public function getTitle(): string
    {
        return 'Dispatch Statistics & Export';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransportStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export Dispatches CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $rows = $this->getFilteredTableQuery()->get();
                    return $this->streamDispatchesCsv($rows);
                }),
        ];
    }

    public function table(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $table
            ->query(
                Dispatch::with('booking')
                    ->where('transport_company_id', $user->transport_company_id)
                    ->latest('flight_date')
            )
            ->columns([
                TextColumn::make('dispatch_ref')
                    ->label('Dispatch Ref')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('booking.booking_ref')
                    ->label('Booking Ref')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('pickup_time')
                    ->label('Time')
                    ->time('H:i'),

                TextColumn::make('total_pax')
                    ->label('PAX')
                    ->alignCenter(),

                TextColumn::make('transport_cost')
                    ->label('Cost (MAD)')
                    ->money('MAD')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info'    => 'in_progress',
                        'gray'    => 'delivered',
                        'danger'  => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'delivered'   => 'Delivered',
                        'cancelled'   => 'Cancelled',
                    ]),
                Filter::make('flight_date')
                    ->label('Flight Date Range')
                    ->form([
                        DatePicker::make('from')->label('From')->native(false),
                        DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('flight_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('flight_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('From: ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString())
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('Until: ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString())
                                ->removeField('until');
                        }
                        return $indicators;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export_selected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(fn (Collection $records) => $this->streamDispatchesCsv($records)),
                ]),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = $this->getFilteredTableQuery()->get();
        return $this->streamDispatchesCsv($rows);
    }

    private function streamDispatchesCsv($rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Dispatch Ref', 'Booking Ref', 'Flight Date', 'Time', 'Total PAX', 'Pickup Location', 'Dropoff Location', 'Cost (MAD)', 'Status']);
            
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->dispatch_ref,
                    $r->booking?->booking_ref,
                    optional($r->flight_date)?->format('d/m/Y'),
                    $r->pickup_time,
                    $r->total_pax,
                    $r->pickup_location,
                    $r->dropoff_location,
                    number_format((float)$r->transport_cost, 2, '.', ''),
                    $r->status,
                ]);
            }
            fclose($out);
        }, 'dispatches-export-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
