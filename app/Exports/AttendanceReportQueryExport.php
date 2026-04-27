<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportQueryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Builder $query
    ) {}

    public function query()
    {
        return $this->query->with(['product', 'partner', 'customers']);
    }

    public function headings(): array
    {
        return [
            'Booking Ref',
            'Type',
            'Flight Date',
            'Flight Time',
            'Product',
            'Partner Name',
            'Adults',
            'Children',
            'Status',
            'Attendance',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->booking_ref,
            ucfirst($booking->type ?? 'Regular'),
            $booking->flight_date?->format('d/m/Y') ?? '—',
            $booking->flight_time ? \Carbon\Carbon::parse($booking->flight_time)->format('H:i') : '—',
            $booking->product?->name ?? '—',
            $booking->partner?->company_name ?? '—',
            $booking->adult_pax ?? 0,
            $booking->child_pax ?? 0,
            ucfirst($booking->booking_status ?? '—'),
            $booking->getPaxAttendanceLabel(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
