<x-filament-panels::page>
    {{-- Partner Info Infolist --}}
    {{ $this->infolist }}

    {{-- Selected Bookings Summary Bar --}}
    @if (count($this->selectedBookingIds) > 0)
    <x-filament::section>
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Selected</div>
                    <div style="font-size: 18px; font-weight: bold; color: #3b82f6;">{{ count($this->selectedBookingIds) }} booking(s)</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Total Amount</div>
                    <div style="font-size: 18px; font-weight: bold;">MAD {{ number_format($this->selectedTotal, 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Paid</div>
                    <div style="font-size: 18px; font-weight: bold; color: #22c55e;">MAD {{ number_format($this->selectedPaid, 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Balance Due</div>
                    <div style="font-size: 18px; font-weight: bold; color: {{ $this->selectedBalance > 0 ? '#ef4444' : '#22c55e' }};">MAD {{ number_format($this->selectedBalance, 2) }}</div>
                </div>
            </div>
            <div>
                {{ ($this->createInvoiceAction) }}
            </div>
        </div>
    </x-filament::section>
    @endif

    {{-- Bookings Table --}}
    <x-filament::section>
        <x-slot name="heading">Bookings</x-slot>
        {{ $this->table }}
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
