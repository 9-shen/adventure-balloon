<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanceReportQueryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Builder $query
    ) {}

    public function query()
    {
        return $this->query->with(['partner', 'product', 'customers']);
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Flight Date',
            'Partner / Type',
            'PAX',
            'Final Amount',
            'Amount Paid',
            'Balance Due',
            'Payment Status',
            'Payment Method',
        ];
    }

    public function map($booking): array
    {
        $partnerOrType = $booking->partner?->company_name 
            ?? (ucfirst($booking->type) . ' (' . ($booking->booking_source ?? 'Unknown') . ')');

        $pax = ($booking->adult_pax + $booking->child_pax) . ' PAX';
        
        return [
            $booking->booking_ref,
            $booking->flight_date?->format('d/m/Y') ?? '—',
            $partnerOrType,
            $pax,
            number_format((float) $booking->final_amount, 2),
            number_format((float) $booking->amount_paid, 2),
            number_format((float) $booking->balance_due, 2),
            ucfirst($booking->payment_status ?? '—'),
            ucfirst($booking->payment_method ?? '—'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
