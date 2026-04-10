<?php

namespace App\Filament\Partner\Pages;

use App\Models\Booking;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountStatement extends Page
{
    protected string $view = 'filament.partner.pages.account-statement';

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

    // ─── Data ─────────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $partnerId = $user->partner_id;

        $totalBookings = Booking::where('partner_id', $partnerId)
            ->where('type', 'partner')
            ->count();

        $confirmedBookings = Booking::where('partner_id', $partnerId)
            ->where('type', 'partner')
            ->where('booking_status', 'confirmed')
            ->count();

        $totalPax = Booking::where('partner_id', $partnerId)
            ->where('type', 'partner')
            ->selectRaw('SUM(adult_pax + child_pax) as total')
            ->value('total') ?? 0;

        $totalBilled = Invoice::where('partner_id', $partnerId)
            ->whereNotIn('status', ['draft'])
            ->sum('total_amount');

        $totalPaid = Invoice::where('partner_id', $partnerId)
            ->where('status', 'paid')
            ->sum('total_amount');

        $totalDue = Invoice::where('partner_id', $partnerId)
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total_amount');

        $overdueAmount = Invoice::where('partner_id', $partnerId)
            ->where('status', 'overdue')
            ->sum('total_amount');

        return compact(
            'totalBookings', 'confirmedBookings', 'totalPax',
            'totalBilled', 'totalPaid', 'totalDue', 'overdueAmount'
        );
    }

    public function getBookingRows(): \Illuminate\Support\Collection
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return Booking::with('product')
            ->where('partner_id', $user->partner_id)
            ->where('type', 'partner')
            ->select([
                'booking_ref', 'flight_date', 'product_id',
                'adult_pax', 'child_pax', 'final_amount',
                'payment_status', 'booking_status',
            ])
            ->orderByDesc('flight_date')
            ->get();
    }

    public function getInvoiceRows(): \Illuminate\Support\Collection
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return Invoice::where('partner_id', $user->partner_id)
            ->select([
                'invoice_ref', 'period_from', 'period_to',
                'subtotal', 'tax_amount', 'total_amount',
                'status', 'sent_at', 'paid_at',
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    // ─── Actions ──────────────────────────────────────────────────────────────

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

    // ─── CSV Exports ──────────────────────────────────────────────────────────

    public function exportBookingsCsv(): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rows = Booking::with('product')
            ->where('partner_id', $user->partner_id)
            ->where('type', 'partner')
            ->orderByDesc('flight_date')
            ->get();

        $filename = 'bookings-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Booking Ref', 'Flight Date', 'Product',
                'Adults', 'Children', 'Total PAX',
                'Amount (MAD)', 'Payment Status', 'Booking Status',
            ]);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->booking_ref,
                    optional($row->flight_date)?->format('d/m/Y'),
                    $row->product?->name ?? '—',
                    $row->adult_pax,
                    $row->child_pax,
                    $row->adult_pax + $row->child_pax,
                    number_format((float) $row->final_amount, 2),
                    $row->payment_status,
                    $row->booking_status,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportInvoicesCsv(): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rows = Invoice::where('partner_id', $user->partner_id)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'invoices-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Invoice #', 'Period From', 'Period To',
                'Subtotal (MAD)', 'Tax (MAD)', 'Total (MAD)',
                'Status', 'Sent At', 'Paid At',
            ]);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->invoice_ref,
                    optional($row->period_from)?->format('d/m/Y') ?? '—',
                    optional($row->period_to)?->format('d/m/Y') ?? '—',
                    number_format((float) $row->subtotal, 2),
                    number_format((float) $row->tax_amount, 2),
                    number_format((float) $row->total_amount, 2),
                    $row->status,
                    optional($row->sent_at)?->format('d/m/Y') ?? '—',
                    optional($row->paid_at)?->format('d/m/Y') ?? '—',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
