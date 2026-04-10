<x-filament-panels::page>

    {{-- Tab Toggle --}}
    <div class="flex gap-2 mb-4">
        <button
            wire:click="switchTab('bookings')"
            @class([
                'inline-flex items-center gap-1.5 w-4 h-4 px-4 py-2 rounded-lg text-sm font-semibold transition',
                'bg-primary-600 text-white shadow' => $activeTab === 'bookings',
                'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' => $activeTab !== 'bookings',
            ])
        >
            <x-heroicon-o-calendar-days class="w-4 h-4" />
            Bookings
        </button>

        <button
            wire:click="switchTab('invoices')"
            @class([
                'inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition',
                'bg-primary-600 text-white shadow' => $activeTab === 'invoices',
                'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' => $activeTab !== 'invoices',
            ])
        >
            <x-heroicon-o-document-text class="w-4 h-4" />
            Invoices
        </button>
    </div>

    {{-- Filament Table --}}
    {{ $this->table }}

</x-filament-panels::page>
