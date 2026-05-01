<div class="space-y-3 py-2">

    @if(empty($links))
        <div class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
            <x-heroicon-o-exclamation-triangle class="h-5 w-5 flex-shrink-0 text-amber-500" />
            <p class="text-sm text-amber-700 dark:text-amber-400">
                No drivers with phone numbers found for this dispatch.
            </p>
        </div>
    @else
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
            Click <strong>Open WhatsApp</strong> for each driver to send the dispatch assignment message via WhatsApp Web.
            Each link opens in a new tab with the message pre-filled.
        </p>

        @foreach($links as $link)
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <x-heroicon-o-device-phone-mobile class="h-5 w-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $link['name'] }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $link['phone'] }}</p>
                    </div>
                </div>

                <a
                    href="{{ $link['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                    Open WhatsApp
                </a>
            </div>
        @endforeach

        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
            ⚠️ WhatsApp Web must be open in your browser. Make sure you are logged in before clicking the links.
        </p>
    @endif

</div>
