<div class="space-y-3 py-2">

    @if(empty($links))
    <div class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
        <p class="text-sm text-amber-700 dark:text-amber-400">
            ⚠️ No drivers with phone numbers found for this dispatch.
        </p>
    </div>
    @else
    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
        Click <strong>Open WhatsApp</strong> for each driver. A new browser tab will open with the message pre-filled — just press Send.
    </p>

    @foreach($links as $link)
    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $link['name'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $link['phone'] }}</p>
        </div>

        {{--
                    Use onclick + window.open() to guarantee new tab opens.
                    @js() properly escapes the URL for safe JS embedding.
                    target="_blank" acts as a native HTML fallback.
                --}}
        <a
            href="{{ $link['url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            onclick="window.open({{ Js::from($link['url']) }}, '_blank', 'noopener,noreferrer'); return false;"
            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-dark shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            ↗ Send WhatsApp
        </a>
    </div>
    @endforeach

    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
        ⚠️ Make sure WhatsApp Web is open and you are logged in before clicking.
    </p>
    @endif

</div>