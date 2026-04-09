<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaxStatsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection(): Collection
    {
        return Booking::query()
            ->select([
                'flight_date',
                DB::raw('COUNT(*) as total_flights'),
                DB::raw('SUM(adult_pax + child_pax) as total_pax'),
                DB::raw('SUM(CASE WHEN attendance = "show" THEN adult_pax + child_pax ELSE 0 END) as showed'),
                DB::raw('SUM(CASE WHEN attendance = "no_show" THEN adult_pax + child_pax ELSE 0 END) as no_showed'),
            ])
            ->whereIn('booking_status', ['confirmed', 'completed'])
            ->when($this->filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '>=', $v))
            ->when($this->filters['date_until'] ?? null, fn($q, $v) => $q->whereDate('flight_date', '<=', $v))
            ->when($this->filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->groupBy('flight_date')
            ->orderByDesc('flight_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Flight Date', 'Total Flights', 'Total PAX', 'Showed PAX', 'No-Show PAX', 'No-Show Rate %',
        ];
    }

    public function map($row): array
    {
        $noShowRate = $row->total_pax > 0
            ? round(($row->no_showed / $row->total_pax) * 100, 1)
            : 0;

        return [
            \Carbon\Carbon::parse($row->flight_date)->format('d/m/Y'),
            $row->total_flights,
            $row->total_pax,
            $row->showed,
            $row->no_showed,
            $noShowRate . '%',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
