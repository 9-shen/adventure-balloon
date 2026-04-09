<x-filament-panels::page>
    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 mb-6">
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Total Revenue</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">MAD {{ $this->getTotalRevenue() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Collected</div>
            <div class="text-2xl font-bold text-green-600">MAD {{ $this->getTotalCollected() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Outstanding</div>
            <div class="text-2xl font-bold text-red-500">MAD {{ $this->getTotalOutstanding() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Total Bookings</div>
            <div class="text-2xl font-bold text-blue-600">{{ $this->getTotalBookings() }}</div>
        </div>
    </div>

    {{-- Table --}}
    {{ $this->table }}

    <x-filament-actions::modals />
</x-filament-panels::page>
