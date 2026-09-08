<?php

namespace App\Http\Controllers\Physician;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('physician.auth.login');
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('physician')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $physician = Auth::guard('physician')->user();
            if (! $physician->isActive()) {
                Auth::guard('physician')->logout();

                return back()->withErrors(['email' => 'Your account is not active.']);
            }

            return redirect()->intended(route('physician.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('physician')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('physician.login');
    }
}
