<?php

namespace App\Filament\Transport\Pages;

use App\Filament\Transport\Widgets\TransportStatsWidget;
use App\Models\Dispatch;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                ->action('exportCsv'),
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
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public function exportCsv(): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rows = Dispatch::with('booking')
            ->where('transport_company_id', $user->transport_company_id)
            ->orderByDesc('flight_date')
            ->get();

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
