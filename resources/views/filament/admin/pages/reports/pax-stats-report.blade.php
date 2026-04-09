<x-filament-panels::page>
    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 mb-6">
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Total Flights</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getTotalFlights() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Total PAX</div>
            <div class="text-2xl font-bold text-blue-600">{{ $this->getTotalPax() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Avg PAX / Flight</div>
            <div class="text-2xl font-bold text-indigo-600">{{ $this->getAvgPaxPerFlight() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">No-Show Rate</div>
            <div class="text-2xl font-bold text-red-500">{{ $this->getNoShowRate() }}</div>
        </div>
    </div>

    {{-- Table --}}
    {{ $this->table }}

    <x-filament-actions::modals />
</x-filament-panels::page>
