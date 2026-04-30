<x-filament-panels::page>
    <x-filament::form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Save Profile
            </x-filament::button>
        </div>
    </x-filament::form>
</x-filament-panels::page>
