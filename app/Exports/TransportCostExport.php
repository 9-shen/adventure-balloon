<?php

namespace App\Exports;

use App\Models\Dispatch;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class TransportCostExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        return Dispatch::query()
            ->with(['transportCompany', 'booking'])
            ->withCount('dispatchDriverRows as vehicles_count')
            ->when($this->filters['transport_company_id'] ?? null, fn($q, $v) => $q->where('transport_company_id', $v))
            ->when($this->filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '>=', $v))
            ->when($this->filters['date_until'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '<=', $v))
            ->when($this->filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when(isset($this->filters['billed']) && $this->filters['billed'] === 'yes', fn($q) => $q->whereNotNull('billed_at'))
            ->when(isset($this->filters['billed']) && $this->filters['billed'] === 'no', fn($q) => $q->whereNull('billed_at'))
            ->orderByDesc('flight_date');
    }

    public function headings(): array
    {
        return [
            'Dispatch Ref', 'Booking Ref', 'Transport Company',
            'Flight Date', 'PAX', 'Vehicles',
            'Transport Cost (MAD)', 'Status', 'Billed',
        ];
    }

    public function map($dispatch): array
    {
        return [
            $dispatch->dispatch_ref,
            $dispatch->booking?->booking_ref ?? '—',
            $dispatch->transportCompany?->company_name ?? '—',
            $dispatch->flight_date?->format('d/m/Y'),
            $dispatch->total_pax,
            $dispatch->vehicles_count ?? 0,
            number_format((float) $dispatch->transport_cost, 2),
            ucfirst(str_replace('_', ' ', $dispatch->status)),
            $dispatch->billed_at ? 'Yes' : 'No',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
