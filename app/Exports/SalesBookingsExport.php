<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesBookingsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Builder $query
    ) {}

    public function query(): Builder
    {
        return $this->query->with(['product', 'customers']);
    }

    public function headings(): array
    {
        $currency = app(\App\Settings\AppSettings::class)->getIsoCurrency();
        return [
            'Reference',
            'Flight Date',
            'Product',
            'Adults',
            'Children',
            'Total PAX',
            "Base Adult Price ({$currency})",
            "Base Child Price ({$currency})",
            "Adult Total ({$currency})",
            "Child Total ({$currency})",
            "Discount Amount ({$currency})",
            'Discount Reason',
            "Final Amount ({$currency})",
            "Amount Paid ({$currency})",
            "Balance Due ({$currency})",
            'Payment Status',
            'Payment Method',
            'Booking Status',
            'Pickup Location',
            'Drop-off Location',
            'Notes',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->booking_ref,
            $booking->flight_date?->format('d/m/Y') ?? '—',
            $booking->product?->name ?? '—',
            $booking->adult_pax,
            $booking->child_pax,
            $booking->adult_pax + $booking->child_pax,
            (float) $booking->base_adult_price,
            (float) $booking->base_child_price,
            (float) $booking->adult_total,
            (float) $booking->child_total,
            (float) $booking->discount_amount,
            $booking->discount_reason ?? '',
            (float) $booking->final_amount,
            (float) $booking->amount_paid,
            (float) $booking->balance_due,
            ucfirst($booking->payment_status ?? '—'),
            ucfirst($booking->payment_method ?? '—'),
            ucfirst($booking->booking_status ?? '—'),
            $booking->pickup_location  ?? '—',
            $booking->dropoff_location ?? '—',
            $booking->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
