<div style="padding: 8px 0;">

    @if(empty($links))
        <div style="display:flex;align-items:center;gap:12px;border:1px solid #fcd34d;background:#fffbeb;border-radius:8px;padding:16px;">
            <p style="font-size:14px;color:#92400e;margin:0;">
                ⚠️ No drivers with phone numbers found for this dispatch.
            </p>
        </div>
    @else
        <p style="font-size:14px;color:#6b7280;margin:0 0 12px 0;">
            Click <strong>Open WhatsApp</strong> for each driver. A new browser tab will open with the message pre-filled — just press Send.
        </p>

        @foreach($links as $link)
            <div style="display:flex;align-items:center;justify-content:space-between;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;margin-bottom:10px;background:#fff;">
                <div>
                    <p style="font-weight:600;font-size:15px;color:#111827;margin:0 0 3px 0;">{{ $link['name'] }}</p>
                    <p style="font-size:13px;color:#6b7280;margin:0;">{{ $link['phone'] }}</p>
                </div>

                {{-- onclick + window.open guarantees new tab regardless of Livewire/panel CSS --}}
                <a
                    href="{{ $link['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    onclick="window.open({{ Js::from($link['url']) }}, '_blank', 'noopener,noreferrer'); return false;"
                    style="display:inline-flex;align-items:center;gap:8px;background:#16a34a;color:#fff;font-size:13px;font-weight:600;padding:8px 16px;border-radius:8px;text-decoration:none;transition:background 0.15s;"
                    onmouseover="this.style.background='#15803d'"
                    onmouseout="this.style.background='#16a34a'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    Send WhatsApp
                </a>
            </div>
        @endforeach

        <p style="font-size:12px;color:#9ca3af;margin-top:8px;">
            ⚠️ Make sure WhatsApp Web is open and you are logged in before clicking.
        </p>
    @endif

</div>