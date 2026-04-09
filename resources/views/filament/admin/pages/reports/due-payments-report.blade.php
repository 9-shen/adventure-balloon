<x-filament-panels::page>
    {{-- Stats Bar --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Total Outstanding</div>
            <div class="text-2xl font-bold text-red-500">MAD {{ $this->getTotalOutstanding() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Due Bookings</div>
            <div class="text-2xl font-bold text-orange-500">{{ $this->getDueCount() }}</div>
        </div>
        <div class="fi-card rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Highest Single Balance</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">MAD {{ $this->getHighestBalance() }}</div>
        </div>
    </div>

    {{-- Table --}}
    {{ $this->table }}

    <x-filament-actions::modals />
</x-filament-panels::page>
