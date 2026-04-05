@php
    $colors = [
        'full'    => ['bg' => 'bg-red-50 dark:bg-red-950',    'border' => 'border-red-400',   'icon' => 'text-red-500',    'text' => 'text-red-800 dark:text-red-200',    'badge' => 'bg-red-500'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-950', 'border' => 'border-amber-400', 'icon' => 'text-amber-500',  'text' => 'text-amber-800 dark:text-amber-200', 'badge' => 'bg-amber-500'],
    ];
    $c = $colors[$status] ?? $colors['warning'];
@endphp

<div class="rounded-xl border-2 {{ $c['border'] }} {{ $c['bg'] }} p-4 shadow-sm">
    <div class="flex items-start gap-4">
        <div class="mt-0.5 shrink-0">
            @if ($status === 'full')
                <svg class="h-7 w-7 {{ $c['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            @else
                <svg class="h-7 w-7 {{ $c['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            @endif
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <p class="text-sm font-bold {{ $c['text'] }}">
                    @if ($status === 'full')
                        🔴 Today is FULLY BOOKED — No more PAX available!
                    @else
                        🟡 PAX Capacity Warning — Only {{ $remaining }} seats remaining today
                    @endif
                </p>
            </div>
            <p class="mt-1 text-xs {{ $c['text'] }} opacity-80">
                {{ $bookedToday }} / {{ $capacity }} passengers booked today.
                @if ($status !== 'full')
                    Alert triggered at {{ $threshold }} remaining.
                @endif
            </p>
        </div>

        <div class="shrink-0">
            <span class="inline-flex items-center rounded-full {{ $c['badge'] }} px-3 py-1 text-xs font-semibold text-white">
                {{ $remaining }} left
            </span>
        </div>
    </div>
</div>
