<?php

namespace App\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Custom logout response that redirects all Filament panel users
 * to the application home page (/) after signing out,
 * regardless of which panel they were using.
 */
class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect('/');
    }
}
