<?php

namespace App\Exports;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class RevenueReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        // When specific IDs are passed (selected rows), export only those
        if (!empty($this->filters['ids'])) {
            return Booking::query()
                ->with(['partner', 'product'])
                ->whereIn('id', $this->filters['ids'])
                ->orderBy('flight_date', 'desc');
        }

        return Booking::query()
            ->with(['partner', 'product'])
            ->when($this->filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '>=', $v))
            ->when($this->filters['date_until'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '<=', $v))
            ->when($this->filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($this->filters['product_id'] ?? null, fn($q, $v) => $q->where('product_id', $v))
            ->when($this->filters['payment_status'] ?? null, fn($q, $v) => $q->where('payment_status', $v))
            ->when($this->filters['booking_status'] ?? null, fn($q, $v) => $q->where('booking_status', $v))
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
            ->orderBy('flight_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Booking Ref', 'Type', 'Partner / Source', 'Product',
            'Flight Date', 'Adults', 'Children', 'Total PAX',
            'Final Amount (MAD)', 'Amount Paid (MAD)', 'Balance Due (MAD)',
            'Payment Status', 'Booking Status',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->booking_ref,
            strtoupper($booking->type),
            $booking->partner?->company_name ?? $booking->booking_source ?? '—',
            $booking->product?->name ?? '—',
            $booking->flight_date?->format('d/m/Y'),
            $booking->adult_pax,
            $booking->child_pax,
            $booking->adult_pax + $booking->child_pax,
            number_format((float) $booking->final_amount, 2),
            number_format((float) $booking->amount_paid, 2),
            number_format((float) $booking->balance_due, 2),
            ucfirst($booking->payment_status ?? '—'),
            ucfirst($booking->booking_status ?? '—'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
