<?php

namespace App\Exports;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FlightStatsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function collection()
    {
        $query = Booking::query()
            ->select([
                DB::raw('MIN(id) as id'),
                'flight_date',
                'type',
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(adult_pax + child_pax) as total_pax'),
                DB::raw('SUM(adult_pax) as total_adults'),
                DB::raw('SUM(child_pax) as total_children'),
            ])
            ->whereIn('booking_status', ['confirmed', 'pending', 'completed'])
            ->when($this->filters['date_from']  ?? null, fn ($q, $v) => $q->whereDate('flight_date', '>=', $v))
            ->when($this->filters['date_until'] ?? null, fn ($q, $v) => $q->whereDate('flight_date', '<=', $v))
            ->when($this->filters['type']       ?? null, fn ($q, $v) => $q->where('type', $v))
            ->groupBy('flight_date', 'type')
            ->orderByDesc('flight_date');

        // ── Selected rows export ───────────────────────────────────────────────
        // Composite keys are in the format "2026-04-30_regular" (flight_date_type)
        if (!empty($this->filters['groups'])) {
            $pairs = collect($this->filters['groups'])->map(function (string $key): array {
                $parts = explode('_', $key, 2);
                return ['date' => $parts[0] ?? '', 'type' => $parts[1] ?? ''];
            });

            $query->where(function ($q) use ($pairs) {
                foreach ($pairs as $pair) {
                    $q->orWhere(function ($inner) use ($pair) {
                        $inner->whereDate('flight_date', $pair['date'])
                              ->where('type', $pair['type']);
                    });
                }
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Flight Date',
            'Type',
            'Total Bookings',
            'Total PAX',
            'Adults',
            'Children',
            'Avg PAX / Booking',
        ];
    }

    public function map($row): array
    {
        $avg = $row->total_bookings > 0
            ? number_format($row->total_pax / $row->total_bookings, 1)
            : '—';

        return [
            \Carbon\Carbon::parse($row->flight_date)->format('d/m/Y'),
            strtoupper($row->type ?? '—'),
            $row->total_bookings,
            $row->total_pax,
            $row->total_adults,
            $row->total_children,
            $avg,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
