<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingCalendarPage extends Page
{
    public function getView(): string
    {
        return 'filament.admin.pages.booking-calendar';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::OutlinedCalendarDays;
    }

    public static function getNavigationLabel(): string
    {
        return 'Booking Calendar';
    }

    protected static ?string $title = 'Booking Calendar';

    public static function getNavigationGroup(): ?string
    {
        return 'Bookings';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    // ── Livewire State ────────────────────────────────────────────────────────

    public int $year;
    public int $month;
    public ?string $selectedDate = null;

    // ── Access Control ────────────────────────────────────────────────────────

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->year  = now()->year;
        $this->month = now()->month;
    }

    // ── Month Navigation ──────────────────────────────────────────────────────

    public function previousMonth(): void
    {
        $d = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year        = $d->year;
        $this->month       = $d->month;
        $this->selectedDate = null;
    }

    public function nextMonth(): void
    {
        $d = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year        = $d->year;
        $this->month       = $d->month;
        $this->selectedDate = null;
    }

    // ── Date Selection ────────────────────────────────────────────────────────

    public function selectDate(string $date): void
    {
        $this->selectedDate = ($this->selectedDate === $date) ? null : $date;
    }

    // ── Data: Calendar Grid ───────────────────────────────────────────────────

    protected function getBookingsByDate(): Collection
    {
        return Booking::query()
            ->whereMonth('flight_date', $this->month)
            ->whereYear('flight_date',  $this->year)
            ->whereNotIn('booking_status', ['cancelled'])
            ->selectRaw('
                flight_date,
                COUNT(*) as total_bookings,
                SUM(final_amount) as total_revenue,
                SUM(adult_pax + child_pax) as total_pax
            ')
            ->groupBy('flight_date')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->flight_date)->format('Y-m-d'));
    }

    protected function buildCalendarDays(): array
    {
        $byDate      = $this->getBookingsByDate();
        $start       = Carbon::create($this->year, $this->month, 1);
        $daysInMonth = $start->daysInMonth;
        $offset      = $start->dayOfWeek; // 0=Sun…6=Sat

        // Leading empty cells so day 1 falls on the correct weekday column
        $days = array_fill(0, $offset, null);

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date    = Carbon::create($this->year, $this->month, $day);
            $key     = $date->format('Y-m-d');
            $rowData = $byDate->get($key);

            $days[] = [
                'day'            => $day,
                'date'           => $key,
                'is_today'       => $date->isToday(),
                'total_bookings' => (int)   ($rowData?->total_bookings ?? 0),
                'total_revenue'  => (float) ($rowData?->total_revenue  ?? 0),
                'total_pax'      => (int)   ($rowData?->total_pax      ?? 0),
            ];
        }

        return $days;
    }

    // ── Data: Sidebar Stats ───────────────────────────────────────────────────

    protected function getMonthStats(): array
    {
        $row = Booking::query()
            ->whereMonth('flight_date', $this->month)
            ->whereYear('flight_date',  $this->year)
            ->whereNotIn('booking_status', ['cancelled'])
            ->selectRaw('COUNT(*) as total, SUM(final_amount) as revenue')
            ->first();

        $total   = (int)   ($row?->total   ?? 0);
        $revenue = (float) ($row?->revenue ?? 0);

        return [
            'total_bookings'    => $total,
            'total_revenue'     => $revenue,
            'avg_booking_value' => $total > 0 ? round($revenue / $total) : 0,
        ];
    }

    protected function getTodayBreakdown(): array
    {
        return Booking::query()
            ->with('product:id,name')
            ->whereDate('flight_date', today())
            ->whereNotIn('booking_status', ['cancelled'])
            ->get()
            ->groupBy('product_id')
            ->map(fn($group) => [
                'name'     => $group->first()->product?->name ?? 'Unknown',
                'pax'      => $group->sum(fn($b) => $b->adult_pax + $b->child_pax),
                'bookings' => $group->count(),
            ])
            ->values()
            ->toArray();
    }

    // ── Data: Selected Day Detail ─────────────────────────────────────────────

    protected function getSelectedDayBookings(): array
    {
        if (! $this->selectedDate) {
            return [];
        }

        return Booking::query()
            ->with('product:id,name')
            ->whereDate('flight_date', $this->selectedDate)
            ->whereNotIn('booking_status', ['cancelled'])
            ->orderBy('flight_time')
            ->get()
            ->map(fn($b) => [
                'ref'            => $b->booking_ref,
                'product'        => $b->product?->name ?? '—',
                'type'           => $b->type,
                'pax'            => $b->adult_pax + $b->child_pax,
                'amount'         => $b->final_amount,
                'payment_status' => $b->payment_status,
                'booking_status' => $b->booking_status,
            ])
            ->toArray();
    }

    // ── View Data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        return [
            'calendarDays'        => $this->buildCalendarDays(),
            'monthStats'          => $this->getMonthStats(),
            'todayBreakdown'      => $this->getTodayBreakdown(),
            'selectedDayBookings' => $this->getSelectedDayBookings(),
            'currentDate'         => Carbon::create($this->year, $this->month, 1),
        ];
    }

    // ── Header Actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user?->hasAnyRole(['super_admin', 'admin', 'manager'])) {
            return [];
        }

        return [
            Action::make('addBooking')
                ->label('Add Booking')
                ->icon('heroicon-o-plus')
                ->url(\App\Filament\Admin\Resources\Bookings\BookingResource::getUrl('create'))
                ->color('primary'),
        ];
    }
}
