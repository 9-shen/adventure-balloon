@php
$isDanger = $status === 'full';
$isWarning = $status === 'warning';
$pct = $capacity > 0 ? round(($bookedToday / $capacity) * 100) : 0;
@endphp

<x-filament-widgets::widget class="fi-pax-alert-widget">
    <x-filament::section
        :color="$isDanger ? 'danger' : 'warning'"
        icon="heroicon-o-exclamation-triangle"
        :icon-color="$isDanger ? 'danger' : 'warning'">
        <x-slot name="heading">
            @if ($isDanger)
            Today is FULLY BOOKED — No seats available!
            @else
            PAX Capacity Warning — Only {{ $remaining }} seats remaining today
            @endif
        </x-slot>

        <x-slot name="description">
            {{ $bookedToday }} / {{ $capacity }} passengers booked today.
            @if ($isWarning)
            Alert triggers when ≤ {{ $threshold }} remaining.
            @endif
        </x-slot>

        {{-- Stats row --}}
        <div class="flex items-center justify-between gap-4 mt-1">
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-2xl font-bold
                        @if($isDanger) text-danger-600 dark:text-danger-400
                        @else text-warning-600 dark:text-warning-400
                        @endif">
                        {{ $remaining }} Seats left
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $bookedToday }} Booked</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $capacity }} Capacity</p>
                </div>
            </div>

            {{-- Badge --}}
            <div>
                @if ($isDanger)
                <x-filament::badge color="danger" size="xl">FULL</x-filament::badge>
                @else
                <x-filament::badge color="warning" size="xl">{{ $pct }}% Used</x-filament::badge>
                @endif
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="mt-4">
            <div class="w-full rounded-full h-2 bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-2 rounded-full transition-all duration-500
                    @if($isDanger) bg-danger-500 @else bg-warning-500 @endif"
                    style="width: {{ min($pct, 100) }}%">
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">{{ $pct }}% of daily capacity used</p>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>