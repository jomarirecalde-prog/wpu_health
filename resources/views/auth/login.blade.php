@extends('layouts.admin')

@section('title', $showRegister ? __('Register') : __('Sign In'))

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --al-primary: #2563EB;
            --al-secondary: #3B82F6;
            --al-bg: #F8FAFC;
            --al-danger: #EF4444;
            --al-text: #111827;
            --al-muted: #6B7280;
            --al-surface: rgba(255, 255, 255, 0.78);
            --al-border: rgba(148, 163, 184, 0.35);
            --al-panel-left: linear-gradient(155deg, #0f172a 0%, #1e3a8a 48%, #2563eb 100%);
            --al-radius: 22px;
            --al-font: 'Inter', system-ui, sans-serif;
        }
        main:has(.auth-shell) { min-height: 100vh; max-width: none; margin: 0; padding: 0; background: var(--al-bg); font-family: var(--al-font); }
        .auth-shell { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 0.45fr) minmax(0, 0.55fr); }
        .auth-brand { padding: clamp(2rem, 4vw, 3.5rem); color: #fff; background: var(--al-panel-left); display: flex; flex-direction: column; justify-content: space-between; }
        .auth-brand h2 { font-size: clamp(1.75rem, 3vw, 2.25rem); font-weight: 700; margin-bottom: 0.75rem; max-width: 18ch; }
        .auth-brand p { opacity: 0.85; line-height: 1.65; max-width: 36ch; }
        .auth-features { list-style: none; display: grid; gap: 0.75rem; margin-top: 2rem; }
        .auth-features li { display: flex; gap: 0.75rem; font-size: 0.875rem; opacity: 0.9; }
        .auth-form-panel { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: clamp(1.5rem, 4vw, 3rem); }
        .auth-form-wrap { width: 100%; max-width: 28rem; display: flex; flex-direction: column; align-items: center; }
        .auth-card { width: 100%; background: var(--al-surface); backdrop-filter: blur(24px); border: 1px solid var(--al-border); border-radius: var(--al-radius); padding: clamp(1.75rem, 4vw, 2.5rem); box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25); }
        .auth-back-home { margin-top: 1.25rem; text-align: center; font-size: 0.8125rem; }
        .auth-back-home a { color: var(--al-primary); font-weight: 600; text-decoration: none; }
        .auth-back-home a:hover { text-decoration: underline; }
        .auth-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; background: rgba(148,163,184,0.12); padding: 0.35rem; border-radius: 12px; }
        .auth-tab { flex: 1; text-align: center; padding: 0.65rem 1rem; border-radius: 10px; font-weight: 600; font-size: 0.875rem; color: var(--al-muted); text-decoration: none; transition: all 0.2s; }
        .auth-tab.is-active { background: #fff; color: var(--al-primary); box-shadow: 0 2px 8px rgba(37,99,235,0.12); }
        .auth-welcome h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--al-text); }
        .auth-welcome p { color: var(--al-muted); font-size: 0.9375rem; margin-bottom: 1.5rem; }
        .auth-field { margin-bottom: 1rem; }
        .auth-field label { display: block; font-size: 0.8125rem; font-weight: 600; color: var(--al-muted); margin-bottom: 0.35rem; }
        .auth-input { width: 100%; min-height: 2.75rem; padding: 0.65rem 0.9rem; border: 1.5px solid var(--al-border); border-radius: 12px; font: inherit; color: var(--al-text); background: #fff; }
        .auth-input:focus { outline: none; border-color: var(--al-primary); box-shadow: 0 0 0 4px rgba(37,99,235,0.15); }
        .auth-input[aria-invalid="true"] { border-color: var(--al-danger); }
        .auth-error { color: var(--al-danger); font-size: 0.8125rem; margin-top: 0.35rem; }
        .auth-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem; }
        .auth-remember { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--al-muted); }
        .auth-submit { width: 100%; min-height: 3rem; border: none; border-radius: 12px; background: linear-gradient(135deg, var(--al-secondary), var(--al-primary)); color: #fff; font-weight: 600; font-size: 0.975rem; cursor: pointer; box-shadow: 0 8px 20px -6px rgba(37,99,235,0.55); }
        .auth-submit:hover { filter: brightness(1.05); transform: translateY(-1px); }
        .auth-footer { margin-top: 1.5rem; text-align: center; font-size: 0.8125rem; color: var(--al-muted); }
        .auth-footer a { color: var(--al-primary); font-weight: 600; text-decoration: none; }
        .auth-panel { display: none; }
        .auth-panel.is-active { display: block; }
        @media (max-width: 960px) { .auth-shell { grid-template-columns: 1fr; } .auth-brand { min-height: auto; } .auth-features { display: none; } }
    </style>
@endpush

@section('content')
    @php
        $brandLogo = file_exists(public_path('logo.png')) ? asset('public/logo.png') : null;
        $loginErrors = $errors->has('identifier') || $errors->has('password');
        $registerErrors = $errors->has('name') || $errors->has('email') || $errors->has('password') || $errors->has('contact_number');
        if ($registerErrors) { $showRegister = true; }
    @endphp

    <div class="auth-shell">
        <aside class="auth-brand" aria-label="{{ __('WPU Health Services') }}">
            <div>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:2rem">
                    @if ($brandLogo)
                        <img src="{{ $brandLogo }}" alt="{{ config('app.name') }}" width="56" height="56" style="border-radius:14px;background:rgba(255,255,255,0.12);padding:0.35rem">
                    @endif
                    <div>
                        <div style="font-weight:600;font-size:0.9375rem">{{ config('app.name') }}</div>
                        <div style="font-size:0.6875rem;opacity:0.7;text-transform:uppercase;letter-spacing:0.08em">{{ __('WPU Health Services') }}</div>
                    </div>
                </div>
                <h2>{{ __('One portal for all health services') }}</h2>
                <p>{{ __('Patients, physicians, and administrators sign in through the same secure entry point. Your role is determined automatically after authentication.') }}</p>
            </div>
            <ul class="auth-features">
                <li><i class="fas fa-calendar-check" aria-hidden="true"></i><span>{{ __('Book consultations and manage appointments') }}</span></li>
                <li><i class="fas fa-user-md" aria-hidden="true"></i><span>{{ __('Physician schedules, availability, and records') }}</span></li>
                <li><i class="fas fa-shield-alt" aria-hidden="true"></i><span>{{ __('Secure role-based access for every account type') }}</span></li>
            </ul>
        </aside>

        <div class="auth-form-panel">
            <div class="auth-form-wrap">
            <div class="auth-card">
                <div class="auth-tabs" role="tablist">
                    <a href="{{ route('login') }}" class="auth-tab {{ ! $showRegister ? 'is-active' : '' }}" role="tab">{{ __('Sign In') }}</a>
                    <a href="{{ route('register') }}" class="auth-tab {{ $showRegister ? 'is-active' : '' }}" role="tab">{{ __('Register') }}</a>
                </div>

                <div class="auth-panel {{ ! $showRegister ? 'is-active' : '' }}" id="login-panel">
                    <header class="auth-welcome">
                        <h1>{{ __('Welcome Back') }}</h1>
                        <p>{{ __('Sign in with your email or username. No need to choose a portal — we will take you to the right dashboard.') }}</p>
                    </header>

                    <form method="post" action="{{ route('login.store') }}">
                        @csrf
                        <div class="auth-field">
                            <label for="identifier">{{ __('Email or Username') }}</label>
                            <input id="identifier" class="auth-input" name="identifier" type="text" value="{{ old('identifier') }}" required autofocus autocomplete="username" @error('identifier') aria-invalid="true" @enderror>
                            @error('identifier')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="auth-field">
                            <label for="password">{{ __('Password') }}</label>
                            <input id="password" class="auth-input" name="password" type="password" required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                            @error('password')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="auth-options">
                            <label class="auth-remember">
                                <input type="checkbox" name="remember" value="1">
                                <span>{{ __('Remember me') }}</span>
                            </label>
                        </div>
                        <button type="submit" class="auth-submit">{{ __('Sign In') }}</button>
                    </form>

                    <p class="auth-footer">
                        {{ __('Don\'t have an account?') }}
                        <a href="{{ route('register') }}">{{ __('Register') }}</a>
                    </p>
                </div>

                <div class="auth-panel {{ $showRegister ? 'is-active' : '' }}" id="register-panel">
                    <header class="auth-welcome">
                        <h1>{{ __('Create Account') }}</h1>
                        <p>{{ __('Register as a patient or system user. Physician and admin accounts are created by authorized staff.') }}</p>
                    </header>

                    <form method="post" action="{{ route('register.store') }}">
                        @csrf
                        <div class="auth-field">
                            <label for="name">{{ __('Full Name') }}</label>
                            <input id="name" class="auth-input" name="name" value="{{ old('name') }}" required @error('name') aria-invalid="true" @enderror>
                            @error('name')<p class="auth-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="auth-field">
                            <label for="email">{{ __('Email') }}</label>
                            <input id="email" class="auth-input" name="email" type="email" value="{{ old('email') }}" required @error('email') aria-invalid="true" @enderror>
                            @error('email')<p class="auth-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="auth-field">
                            <label for="reg-password">{{ __('Password') }}</label>
                            <input id="reg-password" class="auth-input" name="password" type="password" required @error('password') aria-invalid="true" @enderror>
                            @error('password')<p class="auth-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="auth-field">
                            <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                            <input id="password_confirmation" class="auth-input" name="password_confirmation" type="password" required>
                        </div>
                        <div class="auth-field">
                            <label for="contact_number">{{ __('Contact Number') }}</label>
                            <input id="contact_number" class="auth-input" name="contact_number" value="{{ old('contact_number') }}" required @error('contact_number') aria-invalid="true" @enderror>
                            @error('contact_number')<p class="auth-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="auth-field">
                            <label for="patient_type_id">{{ __('Patient Type') }}</label>
                            <select id="patient_type_id" class="auth-input" name="patient_type_id">
                                <option value="">{{ __('— Select —') }}</option>
                                @foreach ($patientTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('patient_type_id') == $type->id)>{{ $type->type_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="auth-field">
                            <label for="department_id">{{ __('Department') }}</label>
                            <select id="department_id" class="auth-input" name="department_id">
                                <option value="">{{ __('— Select —') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="auth-field">
                            <label for="employee_student_id">{{ __('Student/Employee ID') }}</label>
                            <input id="employee_student_id" class="auth-input" name="employee_student_id" value="{{ old('employee_student_id') }}">
                        </div>
                        <div class="auth-field">
                            <label for="date_of_birth">{{ __('Date of Birth') }}</label>
                            <input id="date_of_birth" class="auth-input" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}">
                        </div>
                        <div class="auth-field">
                            <label for="address">{{ __('Address') }}</label>
                            <textarea id="address" class="auth-input" name="address" rows="2">{{ old('address') }}</textarea>
                        </div>
                        <button type="submit" class="auth-submit">{{ __('Create Account') }}</button>
                    </form>

                    <p class="auth-footer">
                        {{ __('Already have an account?') }}
                        <a href="{{ route('login') }}">{{ __('Sign In') }}</a>
                    </p>
                </div>
            </div>

            <p class="auth-back-home">
                <a href="{{ url('/') }}">{{ __('Back to home') }}</a>
            </p>
            </div>
        </div>
    </div>
@endsection
