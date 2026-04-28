<x-filament-panels::page>

    {{-- ── Native-style Tabs ─────────────────────────────────────────── --}}
    <div class="fi-tabs flex gap-x-1 border-b border-gray-200 dark:border-white/10 overflow-x-auto">

        {{-- Bookings Tab --}}
        <button
            wire:click="switchTab('bookings')"
            @class([
                'fi-tabs-item group flex items-center gap-x-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium outline-none transition duration-75 focus-visible:outline-none',
                'fi-tabs-item-active border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                    => $activeTab === 'bookings',
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300'
                    => $activeTab !== 'bookings',
            ])
        >
            <x-heroicon-o-calendar-days class="w-4 h-4 shrink-0" />
            Bookings Report

            @php
                /** @var \App\Models\User $partnerUser */
                $partnerUser = Auth::user();
                $bookingsCount = \App\Models\Booking::where('partner_id', $partnerUser->partner_id)
                    ->where('type', 'partner')->count();
            @endphp
            <span @class([
                'fi-badge rounded-full px-2 py-0.5 text-xs font-medium transition duration-75',
                'bg-primary-100 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400'
                    => $activeTab === 'bookings',
                'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400'
                    => $activeTab !== 'bookings',
            ])>{{ $bookingsCount }}</span>
        </button>

        {{-- Invoices Tab --}}
        <button
            wire:click="switchTab('invoices')"
            @class([
                'fi-tabs-item group flex items-center gap-x-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium outline-none transition duration-75 focus-visible:outline-none',
                'fi-tabs-item-active border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                    => $activeTab === 'invoices',
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300'
                    => $activeTab !== 'invoices',
            ])
        >
            <x-heroicon-o-document-text class="w-4 h-4 shrink-0" />
            Invoices

            @php
                $invoicesCount = \App\Models\Invoice::where('partner_id', $partnerUser->partner_id)->count();
            @endphp
            <span @class([
                'fi-badge rounded-full px-2 py-0.5 text-xs font-medium transition duration-75',
                'bg-primary-100 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400'
                    => $activeTab === 'invoices',
                'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400'
                    => $activeTab !== 'invoices',
            ])>{{ $invoicesCount }}</span>
        </button>

    </div>

    {{-- ── Table ─────────────────────────────────────────────────────── --}}
    {{ $this->table }}

</x-filament-panels::page>

