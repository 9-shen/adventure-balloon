<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Settings\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniversalLoginController extends Controller
{
    public function showLogin()
    {
        // Already logged in? Redirect to their panel
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            return redirect($this->resolvePanelUrl($user));
        }

        $appSettings = app(AppSettings::class);

        return view('auth.universal-login', compact('appSettings'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find user first to check active status before attempting login
        $user = User::where('email', $credentials['email'])->first();

        if ($user && !$user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Your account has been deactivated. Please contact an administrator.']);
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        return redirect($this->resolvePanelUrl($user));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Resolve the correct Filament panel URL for the authenticated user.
     * Priority: most-specific role first.
     */
    private function resolvePanelUrl(User $user): string
    {
        return match(true) {
            $user->hasAnyRole(['super_admin', 'admin']) => '/admin',
            $user->hasRole('manager')                   => '/manager',
            $user->hasRole('accountant')                => '/accountant',
            $user->hasRole('greeter')                   => '/greeter',
            $user->hasRole('transport')                 => '/transport',
            $user->hasRole('driver')                    => '/driver',
            $user->hasRole('partner')                   => '/partner',
            default                                     => '/admin',
        };
    }
}
