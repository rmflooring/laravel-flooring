<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MobileSessionController extends Controller
{
    public function create(): \Illuminate\Http\Response
    {
        return response(view('mobile.auth.login'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Always remember — this sets the long-lived remember cookie
        if (! Auth::guard('mobile')->attempt($request->only('email', 'password'), remember: true)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('mobile')->user();

        if ($user->hasRole('installer')) {
            return redirect()->intended(route('installer.dashboard'));
        }

        return redirect()->intended(route('mobile.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('mobile')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mobile.login');
    }
}
