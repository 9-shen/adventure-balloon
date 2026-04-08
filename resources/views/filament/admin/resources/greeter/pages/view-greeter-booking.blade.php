<x-filament-panels::page>
    {{-- Booking infolist summary --}}
    {{ $this->infolist }}

    {{-- Per-Passenger Attendance Panel --}}
    <div class="mt-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-users class="w-5 h-5 text-primary-500" />
                        Passenger Attendance
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Mark each passenger individually — Show or No-Show
                    </p>
                </div>

                @php
                    $summary = $this->record->getPaxAttendanceSummary();
                @endphp
                <div class="flex items-center gap-4 text-sm">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-medium">
                        <x-heroicon-s-check-circle class="w-4 h-4" />
                        {{ $summary['show'] }} Show
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 font-medium">
                        <x-heroicon-s-x-circle class="w-4 h-4" />
                        {{ $summary['no_show'] }} No-Show
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-medium">
                        <x-heroicon-s-clock class="w-4 h-4" />
                        {{ $summary['pending'] }} Pending
                    </span>
                </div>
            </div>

            {{-- Passenger Rows --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($customers as $customer)
                    @php
                        $currentAttendance = $customerAttendance[$customer->id] ?? 'pending';
                        $isShow   = $currentAttendance === 'show';
                        $isNoShow = $currentAttendance === 'no_show';
                    @endphp

                    <div class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">

                        {{-- Passenger Info --}}
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            {{-- Avatar Initial --}}
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm
                                @if($isShow) bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300
                                @elseif($isNoShow) bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300
                                @else bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 @endif">
                                {{ strtoupper(substr($customer->full_name, 0, 1)) }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900 dark:text-white text-sm truncate">
                                        {{ $customer->full_name }}
                                    </span>
                                    @if($customer->is_primary)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">
                                            Lead
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                        @if($customer->type === 'child') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300
                                        @else bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 @endif">
                                        {{ ucfirst($customer->type ?? 'adult') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    @if($customer->phone)
                                        <span>📞 {{ $customer->phone }}</span>
                                    @endif
                                    @if($customer->nationality)
                                        <span>🌍 {{ $customer->nationality }}</span>
                                    @endif
                                    @if($customer->weight_kg)
                                        <span>⚖️ {{ $customer->weight_kg }} kg</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Attendance Status Badge --}}
                        <div class="flex-shrink-0">
                            @if($isShow)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-semibold">
                                    <x-heroicon-s-check-circle class="w-4 h-4" />
                                    Showed
                                </span>
                            @elseif($isNoShow)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm font-semibold">
                                    <x-heroicon-s-x-circle class="w-4 h-4" />
                                    No-Show
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-sm font-medium">
                                    <x-heroicon-s-clock class="w-4 h-4" />
                                    Pending
                                </span>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex-shrink-0 flex items-center gap-2">
                            {{-- Mark Show --}}
                            <button
                                wire:click="setCustomerAttendance({{ $customer->id }}, 'show')"
                                wire:loading.attr="disabled"
                                wire:target="setCustomerAttendance({{ $customer->id }}, 'show')"
                                @class([
                                    'inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                                    'ring-2 ring-green-500 shadow-sm bg-green-500 text-white cursor-default' => $isShow,
                                    'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/40 border border-green-200 dark:border-green-800 cursor-pointer' => !$isShow,
                                ])
                                @if($isShow) disabled @endif
                            >
                                <x-heroicon-s-check-circle class="w-4 h-4" />
                                Show
                            </button>

                            {{-- Mark No-Show --}}
                            <button
                                wire:click="setCustomerAttendance({{ $customer->id }}, 'no_show')"
                                wire:loading.attr="disabled"
                                wire:target="setCustomerAttendance({{ $customer->id }}, 'no_show')"
                                @class([
                                    'inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                                    'ring-2 ring-red-500 shadow-sm bg-red-500 text-white cursor-default' => $isNoShow,
                                    'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-800 cursor-pointer' => !$isNoShow,
                                ])
                                @if($isNoShow) disabled @endif
                            >
                                <x-heroicon-s-x-circle class="w-4 h-4" />
                                No-Show
                            </button>

                            {{-- Reset to Pending --}}
                            @if($isShow || $isNoShow)
                                <button
                                    wire:click="setCustomerAttendance({{ $customer->id }}, 'pending')"
                                    wire:loading.attr="disabled"
                                    wire:target="setCustomerAttendance({{ $customer->id }}, 'pending')"
                                    class="inline-flex items-center gap-1 px-2 py-2 rounded-lg text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                                    title="Reset to Pending"
                                >
                                    <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                                </button>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-users class="w-10 h-10 mx-auto mb-3 opacity-40" />
                        <p class="text-sm">No passenger records found for this booking.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>

</x-filament-panels::page>
