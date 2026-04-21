<x-filament-panels::page>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ──────────────────────────────────────────────────────────── --}}
        {{-- CALENDAR (left, 2/3)                                         --}}
        {{-- ──────────────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                {{-- Card header --}}
                <div class="flex items-start justify-between p-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <x-heroicon-o-calendar-days class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $currentDate->format('F Y') }}
                            </h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Click on any date to view detailed booking information
                            </p>
                        </div>
                    </div>

                    {{-- Month navigation --}}
                    <div class="flex items-center gap-1.5">
                        <button
                            wire:click="previousMonth"
                            class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            wire:loading.attr="disabled"
                        >
                            <x-heroicon-o-chevron-left class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                        </button>
                        <button
                            wire:click="nextMonth"
                            class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            wire:loading.attr="disabled"
                        >
                            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                        </button>
                    </div>
                </div>

                {{-- Day-of-week headers --}}
                <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                        <div class="py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $dayName }}
                        </div>
                    @endforeach
                </div>

                {{-- Calendar grid --}}
                <div class="grid grid-cols-7 divide-x divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($calendarDays as $day)

                        {{-- Empty leading cell --}}
                        @if($day === null)
                            <div class="min-h-[88px] bg-gray-50/60 dark:bg-gray-800/20"></div>

                        {{-- Day cell --}}
                        @else
                            <button
                                wire:click="selectDate('{{ $day['date'] }}')"
                                class="min-h-[88px] p-2 text-left transition-all duration-150 group relative
                                    @if($selectedDate === $day['date'])
                                        bg-violet-50 dark:bg-violet-900/20 ring-inset ring-2 ring-violet-400 dark:ring-violet-600
                                    @elseif($day['is_today'])
                                        bg-primary-50/40 dark:bg-primary-900/10
                                    @else
                                        hover:bg-gray-50 dark:hover:bg-gray-800/40
                                    @endif"
                            >
                                {{-- Day number --}}
                                <span class="text-sm font-medium
                                    @if($day['is_today'])
                                        inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-600 text-white text-xs font-bold
                                    @elseif($selectedDate === $day['date'])
                                        text-violet-700 dark:text-violet-300 font-bold
                                    @else
                                        text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white
                                    @endif">
                                    {{ $day['day'] }}
                                </span>

                                {{-- Booking badge (only if there are bookings) --}}
                                @if($day['total_bookings'] > 0)
                                    <div class="mt-1.5 space-y-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-600 text-white shadow-sm">
                                            {{ $day['total_bookings'] }} {{ $day['total_bookings'] === 1 ? 'booking' : 'bookings' }}
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                            {{ number_format($day['total_revenue'], 0) }} MAD
                                        </p>
                                    </div>
                                @endif
                            </button>
                        @endif

                    @endforeach
                </div>

            </div>
        </div>

        {{-- ──────────────────────────────────────────────────────────── --}}
        {{-- SIDEBAR (right, 1/3)                                         --}}
        {{-- ──────────────────────────────────────────────────────────── --}}
        <div class="flex flex-col gap-5">

            {{-- ── This Month Stats ────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                <div class="mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">This Month</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Booking statistics</p>
                </div>

                <div class="space-y-4">
                    <div class="pb-4 border-b border-gray-100 dark:border-gray-800">
                        <p class="text-3xl font-extrabold text-gray-900 dark:text-white leading-none">
                            {{ number_format($monthStats['total_bookings']) }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Total Bookings</p>
                    </div>

                    <div class="pb-4 border-b border-gray-100 dark:border-gray-800">
                        <p class="text-3xl font-extrabold text-gray-900 dark:text-white leading-none">
                            {{ number_format($monthStats['total_revenue'], 0) }}
                            <span class="text-lg font-semibold text-gray-400">MAD</span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Total Revenue</p>
                    </div>

                    <div>
                        <p class="text-3xl font-extrabold text-gray-900 dark:text-white leading-none">
                            {{ number_format($monthStats['avg_booking_value'], 0) }}
                            <span class="text-lg font-semibold text-gray-400">MAD</span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Avg. Booking Value</p>
                    </div>
                </div>
            </div>

            {{-- ── Selected Day Details (shown when a date is clicked) ── --}}
            @if($selectedDate)
                <div class="bg-white dark:bg-gray-900 rounded-2xl border-2 border-violet-300 dark:border-violet-700 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-calendar-days class="w-4 h-4 text-violet-500" />
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Day booking details</p>

                    @forelse($selectedDayBookings as $booking)
                        <div class="flex items-start justify-between py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">
                                    {{ $booking['ref'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $booking['product'] }}
                                </p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span @class([
                                        'inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium',
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $booking['payment_status'] === 'paid',
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' => $booking['payment_status'] === 'partial',
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => $booking['payment_status'] === 'due',
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => ! in_array($booking['payment_status'], ['paid', 'partial', 'due']),
                                    ])>
                                        {{ ucfirst($booking['payment_status']) }}
                                    </span>
                                    @if($booking['type'] === 'partner')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                            Partner
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right ml-2 shrink-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ number_format($booking['amount'], 0) }}
                                    <span class="text-xs font-normal text-gray-400">MAD</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $booking['pax'] }} PAX</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <x-heroicon-o-x-circle class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                            <p class="text-sm text-gray-400 dark:text-gray-500">No bookings for this day</p>
                        </div>
                    @endforelse

                    {{-- Deselect button --}}
                    <button
                        wire:click="selectDate('{{ $selectedDate }}')"
                        class="mt-3 w-full text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors text-center py-1"
                    >
                        ✕ Clear selection
                    </button>
                </div>

            {{-- ── Today's Bookings (default, no date selected) ──────── --}}
            @else
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400" />
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">Today's Bookings</h3>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                        {{ now()->format('F j, Y') }}
                    </p>

                    @forelse($todayBreakdown as $item)
                        <div class="flex items-center justify-between py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $item['name'] }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $item['bookings'] }} {{ $item['bookings'] === 1 ? 'booking' : 'bookings' }}
                                </p>
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $item['pax'] }} <span class="text-xs font-normal text-gray-400">PAX</span>
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <x-heroicon-o-x-circle class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                            <p class="text-sm text-gray-400 dark:text-gray-500">No bookings today</p>
                        </div>
                    @endforelse
                </div>
            @endif

        </div>
    </div>

    {{-- Loading overlay --}}
    <div wire:loading.delay class="fixed inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-sm z-50 flex items-center justify-center pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg px-5 py-3 flex items-center gap-3">
            <svg class="animate-spin h-5 w-5 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Loading…</span>
        </div>
    </div>

</x-filament-panels::page>
