<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    @if (file_exists(public_path('logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('public/logo.png') }}">
    @endif
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --surface: #ffffff;
            --border: #e5e7eb;
            --text: #1f2937;
            --muted: #6b7280;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }
        .admin-top {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .admin-top a { color: #fff; text-decoration: none; }
        .admin-top .brand { font-weight: 600; font-size: 1rem; }
        .admin-top .user { font-size: 0.875rem; opacity: 0.95; }
        .admin-top form { display: inline; }
        .admin-top button {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.35);
            color: #fff;
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8125rem;
        }
        .admin-top button:hover { background: rgba(255,255,255,0.3); }
        main { padding: 1.25rem; max-width: 72rem; margin: 0 auto; }
        .flash {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .flash-success { background: #d1fae5; color: #065f46; }
        .flash-error { background: #fee2e2; color: #991b1b; }
    </style>
    @stack('styles')
</head>
<body>
    @auth('admin')
        <header class="admin-top">
            <a href="{{ route('admin.dashboard') }}" class="brand">{{ config('app.name') }}</a>
            <div style="display:flex;align-items:center;gap:1rem;">
                <span class="user">{{ auth('admin')->user()->username }}</span>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit">{{ __('Log out') }}</button>
                </form>
            </div>
        </header>
    @endauth

    <main>
        @if (session('status'))
            <div class="flash flash-success" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="flash flash-error" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
