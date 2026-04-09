<?php

namespace App\Exports;

use App\Models\Partner;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PartnerSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection(): Collection
    {
        return Partner::query()
            ->with(['bookings' => function ($query) {
                $query->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
                    ->when($this->filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '>=', $v))
                    ->when($this->filters['date_until'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '<=', $v));
            }, 'invoices'])
            ->get()
            ->map(function ($partner) {
                $bookings = $partner->bookings;
                return (object) [
                    'company_name'    => $partner->company_name,
                    'email'           => $partner->email,
                    'status'          => $partner->status,
                    'total_bookings'  => $bookings->count(),
                    'total_pax'       => $bookings->sum(fn($b) => $b->adult_pax + $b->child_pax),
                    'total_revenue'   => $bookings->sum('final_amount'),
                    'total_paid'      => $bookings->sum('amount_paid'),
                    'total_outstanding' => $bookings->sum('balance_due'),
                    'invoices_count'  => $partner->invoices->count(),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Partner', 'Email', 'Status', 'Total Bookings', 'Total PAX',
            'Total Revenue (MAD)', 'Total Paid (MAD)', 'Outstanding (MAD)', 'Invoices Count',
        ];
    }

    public function map($row): array
    {
        return [
            $row->company_name,
            $row->email,
            ucfirst($row->status),
            $row->total_bookings,
            $row->total_pax,
            number_format((float) $row->total_revenue, 2),
            number_format((float) $row->total_paid, 2),
            number_format((float) $row->total_outstanding, 2),
            $row->invoices_count,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
