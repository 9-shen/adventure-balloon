<x-filament-panels::page>
    {{-- Main Settings Form --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap items-center gap-3">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    {{-- Inline Test WhatsApp Form --}}
    <div class="mt-8 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                <x-heroicon-o-chat-bubble-left-ellipsis class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Send Test WhatsApp Message</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Verify your Twilio configuration by sending a test message</p>
            </div>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Recipient Number
                    </label>
                    <input
                        type="text"
                        wire:model="testNumber"
                        placeholder="whatsapp:+212600000000"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    />
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Format: whatsapp:+[country][number]</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Message
                    </label>
                    <textarea
                        wire:model="testMessage"
                        rows="3"
                        placeholder="Enter test message..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    ></textarea>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    wire:click="sendTestWhatsApp"
                    wire:loading.attr="disabled"
                    wire:target="sendTestWhatsApp"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <span wire:loading.remove wire:target="sendTestWhatsApp">
                        <x-heroicon-o-paper-airplane class="h-4 w-4" />
                    </span>
                    <span wire:loading wire:target="sendTestWhatsApp">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="sendTestWhatsApp">Send Test Message</span>
                    <span wire:loading wire:target="sendTestWhatsApp">Sending…</span>
                </button>

                <p class="text-xs text-gray-400 dark:text-gray-500">
                    ⚠️ Save your settings first — the test uses the <strong>saved</strong> Twilio credentials.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
