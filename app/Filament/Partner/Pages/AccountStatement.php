<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Partner\Widgets\AccountStatsWidget;
use App\Models\Booking;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountStatement extends Page implements HasTable
{
    use InteractsWithTable;

    public string $activeTab = 'bookings';

    public function getView(): string
    {
        return 'filament.partner.pages.account-statement';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Account Statement';
    }

    public function getTitle(): string
    {
        return 'Account Statement';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    // ─── Header Widget ─────────────────────────────────────────────────────────
    protected function getHeaderWidgets(): array
    {
        return [
            AccountStatsWidget::class,
        ];
    }

    // ─── CSV Export Actions ────────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_bookings')
                ->label('Export Bookings CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action('exportBookingsCsv'),

            Action::make('export_invoices')
                ->label('Export Invoices CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action('exportInvoicesCsv'),
        ];
    }

    // ─── Filament Table ────────────────────────────────────────────────────────
    public function table(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $partnerId = $user->partner_id;

        if ($this->activeTab === 'invoices') {
            return $this->buildInvoicesTable($table, $partnerId);
        }

        return $this->buildBookingsTable($table, $partnerId);
    }

    private function buildBookingsTable(Table $table, int $partnerId): Table
    {
        return $table
            ->query(
                Booking::with('product')
                    ->where('partner_id', $partnerId)
                    ->where('type', 'partner')
                    ->latest('flight_date')
            )
            ->columns([
                TextColumn::make('booking_ref')
                    ->label('Booking Ref')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('flight_date')
                    ->label('Flight Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),

                TextColumn::make('adult_pax')
                    ->label('Adults')
                    ->alignCenter(),

                TextColumn::make('child_pax')
                    ->label('Children')
                    ->alignCenter(),

                TextColumn::make('final_amount')
                    ->label('Amount (MAD)')
                    ->money('MAD')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger'  => 'due',
                    ]),

                TextColumn::make('booking_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'confirmed',
                        'warning' => 'pending',
                        'danger'  => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('booking_status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->options([
                        'due'     => 'Due',
                        'partial' => 'Partial',
                        'paid'    => 'Paid',
                    ]),
            ])
            ->defaultSort('flight_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function buildInvoicesTable(Table $table, int $partnerId): Table
    {
        return $table
            ->query(
                Invoice::where('partner_id', $partnerId)
                    ->latest()
            )
            ->columns([
                TextColumn::make('invoice_ref')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('period_from')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) =>
                        optional($record->period_from)?->format('d/m/Y')
                        . ' → '
                        . optional($record->period_to)?->format('d/m/Y')
                    ),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('MAD')
                    ->alignRight(),

                TextColumn::make('tax_amount')
                    ->label('Tax')
                    ->money('MAD')
                    ->alignRight(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('MAD')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray'    => 'draft',
                        'info'    => 'sent',
                        'success' => 'paid',
                        'danger'  => 'overdue',
                    ]),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                TextColumn::make('paid_at')
                    ->label('Paid On')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'   => 'Draft',
                        'sent'    => 'Sent',
                        'paid'    => 'Paid',
                        'overdue' => 'Overdue',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25]);
    }

    // ─── Tab switching (Livewire) ──────────────────────────────────────────────
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    // ─── CSV Exports ───────────────────────────────────────────────────────────
    public function exportBookingsCsv(): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rows = Booking::with('product')
            ->where('partner_id', $user->partner_id)
            ->where('type', 'partner')
            ->orderByDesc('flight_date')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Booking Ref','Flight Date','Product','Adults','Children','Total PAX','Amount (MAD)','Payment Status','Booking Status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->booking_ref,
                    optional($r->flight_date)?->format('d/m/Y'),
                    $r->product?->name,
                    $r->adult_pax,
                    $r->child_pax,
                    $r->adult_pax + $r->child_pax,
                    number_format((float)$r->final_amount, 2),
                    $r->payment_status,
                    $r->booking_status,
                ]);
            }
            fclose($out);
        }, 'bookings-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportInvoicesCsv(): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rows = Invoice::where('partner_id', $user->partner_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice #','Period From','Period To','Subtotal (MAD)','Tax (MAD)','Total (MAD)','Status','Sent At','Paid At']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->invoice_ref,
                    optional($r->period_from)?->format('d/m/Y'),
                    optional($r->period_to)?->format('d/m/Y'),
                    number_format((float)$r->subtotal, 2),
                    number_format((float)$r->tax_amount, 2),
                    number_format((float)$r->total_amount, 2),
                    $r->status,
                    optional($r->sent_at)?->format('d/m/Y'),
                    optional($r->paid_at)?->format('d/m/Y'),
                ]);
            }
            fclose($out);
        }, 'invoices-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
