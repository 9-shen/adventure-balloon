<x-filament-panels::page>

    {{-- ── Stats Cards ──────────────────────────────────────────────────── --}}
    @php $s = $this->getStats(); @endphp

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-8">

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Total Bookings</p>
            <p class="mt-1 text-3xl font-bold text-gray-800 dark:text-white">{{ $s['totalBookings'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $s['confirmedBookings'] }} confirmed · {{ $s['totalPax'] }} total PAX</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Total Billed</p>
            <p class="mt-1 text-3xl font-bold text-gray-800 dark:text-white">{{ number_format((float)$s['totalBilled'], 2) }} <span class="text-base text-gray-400">MAD</span></p>
            <p class="text-xs text-gray-400 mt-1">Across all sent/paid invoices</p>
        </div>

        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-500">Total Paid</p>
            <p class="mt-1 text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format((float)$s['totalPaid'], 2) }} <span class="text-base text-emerald-400">MAD</span></p>
            <p class="text-xs text-emerald-400 mt-1">Settled invoices</p>
        </div>

        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-amber-500">Outstanding Due</p>
            <p class="mt-1 text-3xl font-bold text-amber-700 dark:text-amber-300">{{ number_format((float)$s['totalDue'], 2) }} <span class="text-base text-amber-400">MAD</span></p>
            @if($s['overdueAmount'] > 0)
                <p class="text-xs text-red-500 mt-1 font-semibold">⚠ {{ number_format((float)$s['overdueAmount'], 2) }} MAD overdue</p>
            @else
                <p class="text-xs text-amber-400 mt-1">No overdue invoices</p>
            @endif
        </div>

    </div>

    {{-- ── Invoices Table ───────────────────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold text-gray-700 dark:text-gray-200">Invoices</h2>
            <x-filament::button
                wire:click="exportInvoicesCsv"
                size="sm"
                icon="heroicon-o-document-arrow-down"
                color="primary"
            >
                Export CSV
            </x-filament::button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Invoice #</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Period</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Subtotal</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Tax</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Total</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Sent</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Paid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                    @forelse($this->getInvoiceRows() as $inv)
                        @php
                            $statusColor = match($inv->status) {
                                'paid'    => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950',
                                'sent'    => 'text-blue-600 bg-blue-50 dark:bg-blue-950',
                                'overdue' => 'text-red-600 bg-red-50 dark:bg-red-950',
                                default   => 'text-gray-500 bg-gray-50 dark:bg-gray-800',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $inv->invoice_ref }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                {{ optional($inv->period_from)?->format('d/m/Y') ?? '—' }}
                                →
                                {{ optional($inv->period_to)?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format((float)$inv->subtotal, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ number_format((float)$inv->tax_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ number_format((float)$inv->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                    {{ ucfirst($inv->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ optional($inv->sent_at)?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ optional($inv->paid_at)?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No invoices yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Bookings Table ───────────────────────────────────────────────── --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold text-gray-700 dark:text-gray-200">Bookings</h2>
            <x-filament::button
                wire:click="exportBookingsCsv"
                size="sm"
                icon="heroicon-o-arrow-down-tray"
                color="gray"
            >
                Export CSV
            </x-filament::button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Booking Ref</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Flight Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Product</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Adults</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Children</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Amount (MAD)</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Payment</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-xs">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                    @forelse($this->getBookingRows() as $bk)
                        @php
                            $payColor = match($bk->payment_status) {
                                'paid'    => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950',
                                'partial' => 'text-amber-600 bg-amber-50 dark:bg-amber-950',
                                default   => 'text-red-600 bg-red-50 dark:bg-red-950',
                            };
                            $bkColor = match($bk->booking_status) {
                                'confirmed'  => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950',
                                'cancelled'  => 'text-red-600 bg-red-50 dark:bg-red-950',
                                default      => 'text-amber-600 bg-amber-50 dark:bg-amber-950',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $bk->booking_ref }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ optional($bk->flight_date)?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $bk->product?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $bk->adult_pax }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $bk->child_pax }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format((float)$bk->final_amount, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $payColor }}">
                                    {{ ucfirst($bk->payment_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $bkColor }}">
                                    {{ ucfirst($bk->booking_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No bookings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>
