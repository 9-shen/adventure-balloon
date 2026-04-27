<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DispatchesReportQueryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Builder $query
    ) {}

    public function query()
    {
        return $this->query->with(['booking', 'transportCompany']);
    }

    public function headings(): array
    {
        return [
            'Dispatch Ref',
            'Booking Ref',
            'Transport Company',
            'Flight Date',
            'Pickup Time',
            'PAX',
            'Status',
            'Notified At',
        ];
    }

    public function map($dispatch): array
    {
        return [
            $dispatch->dispatch_ref,
            $dispatch->booking?->booking_ref ?? '—',
            $dispatch->transportCompany?->company_name ?? '—',
            $dispatch->flight_date?->format('d/m/Y') ?? '—',
            $dispatch->pickup_time ? \Carbon\Carbon::parse($dispatch->pickup_time)->format('H:i') : '—',
            $dispatch->total_pax ?? '0',
            ucfirst(str_replace('_', ' ', $dispatch->status ?? '—')),
            $dispatch->notified_at ? $dispatch->notified_at->format('d/m/Y H:i') : 'Not sent',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
