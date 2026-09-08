<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PatientType;
use App\Models\PortalUser;
use App\Services\AuthRoleService;
use App\Services\UnifiedAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly UnifiedAuthService $auth,
        private readonly AuthRoleService $roles,
    ) {
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($this->roles->isAuthenticated()) {
            return redirect()->to($this->roles->dashboardUrl());
        }

        return view('auth.login', [
            'showRegister' => $request->boolean('register') || $request->routeIs('register'),
            'patientTypes' => PatientType::query()->orderBy('type_name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->auth->attempt(
            $validated['identifier'],
            $validated['password'],
            $request->boolean('remember'),
        );

        if (! $result['success']) {
            return back()
                ->withErrors(['identifier' => $result['message']])
                ->onlyInput('identifier');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->roles->dashboardUrl());
    }

    public function register(Request $request): RedirectResponse
    {
        if ($this->roles->isAuthenticated()) {
            return redirect()->to($this->roles->dashboardUrl());
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:portal_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'contact_number' => ['required', 'string', 'max:50'],
            'patient_type_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'employee_student_id' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user = PortalUser::query()->create([
            ...$validated,
            'status' => 'active',
        ]);

        Auth::guard('portal')->login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('portal.dashboard')
            ->with('status', __('Welcome! Your account has been created.'));
    }
}
