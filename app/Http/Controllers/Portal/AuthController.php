<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PatientType;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('portal')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::guard('portal')->user();
            if (! $user->isActive()) {
                Auth::guard('portal')->logout();

                return back()->withErrors(['email' => 'Your account is not active.']);
            }

            return redirect()->intended(route('portal.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('portal.auth.register', [
            'patientTypes' => PatientType::query()->orderBy('type_name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function register(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:portal_users,email',
            'password' => 'required|string|min:8|confirmed',
            'contact_number' => 'required|string|max:50',
            'patient_type_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'employee_student_id' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
        ]);

        $user = PortalUser::query()->create($validated);
        Auth::guard('portal')->login($user);

        return redirect()->route('portal.dashboard')->with('status', 'Welcome! Your account has been created.');
    }

    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.home');
    }
}
