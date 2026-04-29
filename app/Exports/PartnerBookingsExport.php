<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PartnerBookingsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Builder $query
    ) {}

    public function query(): Builder
    {
        return $this->query->with(['product', 'guide', 'customers']);
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Your Ref (Partner Reference)',
            'Flight Date',
            'Product',
            'Guide',
            'Adults',
            'Children',
            'Total PAX',
            'Pickup Location',
            'Drop-off Location',
            'Booking Status',
            'Notes',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->booking_ref,
            $booking->partner_reference ?? '—',
            $booking->flight_date?->format('d/m/Y') ?? '—',
            $booking->product?->name ?? '—',
            $booking->guide?->name ?? '—',
            $booking->adult_pax,
            $booking->child_pax,
            $booking->adult_pax + $booking->child_pax,
            $booking->pickup_location  ?? '—',
            $booking->dropoff_location ?? '—',
            ucfirst($booking->booking_status ?? '—'),
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
