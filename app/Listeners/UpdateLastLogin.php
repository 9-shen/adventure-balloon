<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateLastLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        
        if ($user instanceof \App\Models\User) {
            $user->last_login_at = now();
            // Using saveQuietly() to avoid triggering any 'updated' observer events that might cause recursion 
            $user->saveQuietly();
        }
    }
}
