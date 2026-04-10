<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class DuePaymentsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        return Booking::query()
            ->with(['partner', 'product', 'customers'])
            ->where('balance_due', '>', 0)
            ->when($this->filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($this->filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '>=', $v))
            ->when($this->filters['date_until'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '<=', $v))
            ->whereIn('booking_status', ['confirmed', 'pending'])
            ->orderByDesc('balance_due');
    }

    public function headings(): array
    {
        return [
            'Booking Ref', 'Type', 'Partner / Customer', 'Product',
            'Flight Date', 'Total PAX',
            'Final Amount (MAD)', 'Amount Paid (MAD)', 'Balance Due (MAD)',
            'Payment Status',
        ];
    }

    public function map($booking): array
    {
        $name = $booking->partner?->company_name
            ?? optional($booking->customers?->where('is_primary', true)->first() ?? $booking->customers?->first())->full_name
            ?? $booking->booking_source
            ?? '—';

        return [
            $booking->booking_ref,
            strtoupper($booking->type),
            $name,
            $booking->product?->name ?? '—',
            $booking->flight_date?->format('d/m/Y'),
            $booking->adult_pax + $booking->child_pax,
            number_format((float) $booking->final_amount, 2),
            number_format((float) $booking->amount_paid, 2),
            number_format((float) $booking->balance_due, 2),
            ucfirst($booking->payment_status ?? '—'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
