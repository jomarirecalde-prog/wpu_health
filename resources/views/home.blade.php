<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $logo = file_exists(public_path('logo.png')) ? asset('public/logo.png') : null;
        $authRoles = app(\App\Services\AuthRoleService::class);
        $isAuthenticated = $authRoles->isAuthenticated();
    @endphp
    <title>{{ config('app.name') }} — WPU Health Services</title>
    <meta name="description" content="WPU Health Services — book consultations, manage appointments, and access healthcare services for students, employees, physicians, and administrators.">
    @if ($logo)
        <link rel="icon" type="image/png" href="{{ $logo }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
           Theme Variables — Light / Dark (system preference)
           ============================================================ */
        :root {
            --primary: #2563EB;
            --primary-dark: #1d4ed8;
            --secondary: #3B82F6;
            --success: #10B981;
            --bg: #F8FAFC;
            --bg-alt: #EFF6FF;
            --card: #ffffff;
            --text: #1f2937;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --border: #e5e7eb;
            --border-light: #f1f5f9;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 16px rgba(37, 99, 235, 0.08);
            --shadow-lg: 0 12px 40px rgba(37, 99, 235, 0.12);
            --shadow-xl: 0 20px 60px rgba(37, 99, 235, 0.15);
            --glass-bg: rgba(255, 255, 255, 0.72);
            --glass-border: rgba(255, 255, 255, 0.5);
            --header-shadow: 0 4px 30px rgba(0, 0, 0, 0.06);
            --blob-1: rgba(37, 99, 235, 0.12);
            --blob-2: rgba(59, 130, 246, 0.10);
            --blob-3: rgba(16, 185, 129, 0.08);
            --gradient-hero: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 50%, #F0FDF4 100%);
            --radius: 1rem;
            --radius-xl: 1.25rem;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --bg-alt: #1e293b;
                --card: #1e293b;
                --text: #f1f5f9;
                --text-muted: #94a3b8;
                --text-light: #64748b;
                --border: #334155;
                --border-light: #1e293b;
                --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
                --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.25);
                --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.35);
                --shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.45);
                --glass-bg: rgba(15, 23, 42, 0.75);
                --glass-border: rgba(51, 65, 85, 0.5);
                --header-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
                --blob-1: rgba(37, 99, 235, 0.18);
                --blob-2: rgba(59, 130, 246, 0.14);
                --blob-3: rgba(16, 185, 129, 0.10);
                --gradient-hero: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            }
        }

        /* ============================================================
           Base & Reset
           ============================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 5rem;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        img { max-width: 100%; height: auto; display: block; }
        a { color: inherit; text-decoration: none; }
        ul { list-style: none; }

        .container {
            width: 100%;
            max-width: 72rem;
            margin: 0 auto;
            padding: 0 1.25rem;
        }

        /* ============================================================
           Floating Background Blobs
           ============================================================ */
        .bg-decor {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            animation: floatBlob 20s ease-in-out infinite;
        }

        .blob-1 {
            width: 32rem;
            height: 32rem;
            background: var(--blob-1);
            top: -8rem;
            right: -8rem;
        }

        .blob-2 {
            width: 28rem;
            height: 28rem;
            background: var(--blob-2);
            bottom: 20%;
            left: -10rem;
            animation-delay: -7s;
        }

        .blob-3 {
            width: 24rem;
            height: 24rem;
            background: var(--blob-3);
            top: 50%;
            right: 10%;
            animation-delay: -14s;
        }

        @keyframes floatBlob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }

        /* ============================================================
           Sticky Glassmorphism Header
           ============================================================ */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            box-shadow: var(--header-shadow);
            transition: background var(--transition), box-shadow var(--transition);
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4.25rem;
            gap: 1rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--text);
        }

        .brand-logo {
            width: 2.5rem;
            height: 2.5rem;
            object-fit: contain;
            border-radius: 50%;
            border: 2px solid var(--border-light);
            background: var(--card);
        }

        .brand-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
        }

        .nav-links {
            display: none;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
            transition: color var(--transition);
        }

        .nav-links a:hover,
        .nav-links a:focus-visible {
            color: var(--primary);
        }

        /* ============================================================
           Buttons
           ============================================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1.35rem;
            border-radius: 0.625rem;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: transform var(--transition), box-shadow var(--transition), filter var(--transition);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.45);
            filter: brightness(1.05);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .btn-sm {
            padding: 0.55rem 1.1rem;
            font-size: 0.85rem;
        }

        .btn-icon {
            width: 1.125rem;
            height: 1.125rem;
            flex-shrink: 0;
        }

        /* Ripple effect */
        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .btn-primary:hover::after {
            opacity: 1;
        }

        /* ============================================================
           Hero Section
           ============================================================ */
        .hero {
            position: relative;
            z-index: 1;
            padding: 4rem 0 5rem;
            background: var(--gradient-hero);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3rem;
            align-items: center;
        }

        .hero-content {
            animation: fadeSlideUp 0.8s ease-out both;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow-sm);
        }

        .hero-badge-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.85); }
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.25rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin-bottom: 1.25rem;
            color: var(--text);
        }

        .hero h1 .highlight {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 1.05rem;
            color: var(--text-muted);
            max-width: 32rem;
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
        }

        .hero-visual {
            animation: fadeSlideUp 0.8s ease-out 0.2s both;
            display: flex;
            justify-content: center;
        }

        .hero-illustration {
            width: 100%;
            max-width: 28rem;
            animation: floatIllustration 6s ease-in-out infinite;
        }

        @keyframes floatIllustration {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           Wave Separator
           ============================================================ */
        .wave-separator {
            position: relative;
            z-index: 1;
            line-height: 0;
            margin-top: -1px;
        }

        .wave-separator svg {
            width: 100%;
            height: 3.5rem;
        }

        .wave-separator path {
            fill: var(--bg);
        }

        /* ============================================================
           Section Shared Styles
           ============================================================ */
        .section {
            position: relative;
            z-index: 1;
            padding: 5rem 0;
        }

        .section-header {
            text-align: center;
            max-width: 40rem;
            margin: 0 auto 3rem;
        }

        .section-label {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .section-title {
            font-size: clamp(1.75rem, 3.5vw, 2.25rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
        }

        .section-desc {
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* ============================================================
           Feature Cards
           ============================================================ */
        .features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }

        .feature-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
            animation: fadeSlideUp 0.6s ease-out both;
        }

        .feature-card:nth-child(1) { animation-delay: 0.05s; }
        .feature-card:nth-child(2) { animation-delay: 0.1s; }
        .feature-card:nth-child(3) { animation-delay: 0.15s; }
        .feature-card:nth-child(4) { animation-delay: 0.2s; }
        .feature-card:nth-child(5) { animation-delay: 0.25s; }
        .feature-card:nth-child(6) { animation-delay: 0.3s; }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(37, 99, 235, 0.25);
        }

        .feature-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.08));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.15rem;
            color: var(--primary);
        }

        .feature-card h3 {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ============================================================
           System Modules
           ============================================================ */
        .modules-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .module-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.15rem 1.35rem;
            transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
        }

        .module-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
            border-color: rgba(37, 99, 235, 0.2);
        }

        .module-badge {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
        }

        .module-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.15rem;
        }

        .module-info p {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        /* ============================================================
           Why Choose Us
           ============================================================ */
        .why-section {
            background: var(--bg-alt);
        }

        .why-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .why-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem 1.25rem;
            transition: transform var(--transition), box-shadow var(--transition);
        }

        .why-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .why-check {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.12);
            color: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .why-item span {
            font-weight: 500;
            font-size: 0.95rem;
        }

        /* ============================================================
           CTA Banner
           ============================================================ */
        .cta-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: var(--radius-xl);
            padding: 3rem 2rem;
            text-align: center;
            color: #fff;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.12) 0%, transparent 50%);
            pointer-events: none;
        }

        .cta-banner h2 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .cta-banner p {
            opacity: 0.9;
            margin-bottom: 1.75rem;
            max-width: 28rem;
            margin-left: auto;
            margin-right: auto;
            position: relative;
        }

        .cta-banner .btn-primary {
            background: #fff;
            color: var(--primary);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .cta-banner .btn-primary:hover {
            background: #f8fafc;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        /* ============================================================
           Footer
           ============================================================ */
        .site-footer {
            position: relative;
            z-index: 1;
            background: var(--card);
            border-top: 1px solid var(--border);
            padding: 3rem 0 1.5rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-brand {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 0.5rem;
        }

        .footer-tagline {
            font-size: 0.875rem;
            color: var(--text-muted);
            max-width: 20rem;
        }

        .footer-links h5 {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 0.85rem;
        }

        .footer-links ul {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .footer-links a {
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: color var(--transition);
        }

        .footer-links a:hover,
        .footer-links a:focus-visible {
            color: var(--primary);
        }

        .footer-bottom {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.8rem;
            color: var(--text-light);
            text-align: center;
        }

        .footer-version {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.6rem;
            background: var(--bg-alt);
            border-radius: 999px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* ============================================================
           Back to Top
           ============================================================ */
        .back-to-top {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 200;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transform: translateY(1rem);
            transition: opacity var(--transition), visibility var(--transition), transform var(--transition);
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .back-to-top:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .back-to-top:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* ============================================================
           Reveal on Scroll
           ============================================================ */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ============================================================
           Responsive Breakpoints
           ============================================================ */
        @media (min-width: 640px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .modules-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .why-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: 1.5fr 1fr 1fr;
            }

            .footer-bottom {
                flex-direction: row;
                justify-content: space-between;
                text-align: left;
            }
        }

        @media (min-width: 768px) {
            .nav-links {
                display: flex;
            }

            .hero-grid {
                grid-template-columns: 1fr 1fr;
                gap: 4rem;
            }
        }

        @media (min-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .hero {
                padding: 5.5rem 0 6rem;
            }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>

    {{-- Floating background decoration --}}
    <div class="bg-decor" aria-hidden="true">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    {{-- ============================================================
         HEADER
         ============================================================ --}}
    <header class="site-header" role="banner">
        <div class="container header-inner">
            <a href="/" class="brand" aria-label="{{ config('app.name') }} — Home">
                @if ($logo)
                    <img class="brand-logo" src="{{ $logo }}" alt="{{ config('app.name') }} logo">
                @else
                    <span class="brand-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </span>
                @endif
                <span>{{ config('app.name') }}</span>
            </a>

            <nav class="nav-links" aria-label="Main navigation">
                <a href="#features">Features</a>
                <a href="#modules">Modules</a>
                <a href="#contact">Contact</a>
            </nav>

            @if ($isAuthenticated)
                <a class="btn btn-primary btn-sm" href="{{ $authRoles->dashboardUrl() }}" aria-label="{{ $authRoles->dashboardLabel() }}">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    {{ $authRoles->dashboardLabel() }}
                </a>
            @else
                <a class="btn btn-primary btn-sm" href="{{ route('login') }}" aria-label="{{ __('Login / Register') }}">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    {{ __('Login / Register') }}
                </a>
            @endif
        </div>
    </header>

    <main id="main-content">

        {{-- ============================================================
             HERO SECTION
             ============================================================ --}}
        <section class="hero" aria-labelledby="hero-heading">
            <div class="container hero-grid">
                <div class="hero-content">
                    <div class="hero-badge">
                        <span class="hero-badge-dot" aria-hidden="true"></span>
                        Trusted Healthcare Platform
                    </div>
                    <h1 id="hero-heading">
                        <span class="highlight">WPU Health Services</span>
                    </h1>
                    <p class="hero-desc">
                        Your unified gateway to campus health services — book consultations, view physicians,
                        manage appointments, and access role-based dashboards for patients, physicians, and administrators
                        through one secure sign-in.
                    </p>
                    <div class="hero-actions">
                        @if ($isAuthenticated)
                            <a class="btn btn-primary" href="{{ $authRoles->dashboardUrl() }}">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                                </svg>
                                {{ $authRoles->dashboardLabel() }}
                            </a>
                        @else
                            <a class="btn btn-primary" href="{{ route('login') }}">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                    <polyline points="10 17 15 12 10 7"/>
                                    <line x1="15" y1="12" x2="3" y2="12"/>
                                </svg>
                                {{ __('Login / Register') }}
                            </a>
                        @endif
                        <a class="btn btn-secondary" href="{{ route('portal.physicians') }}">
                            {{ __('Available Physicians') }}
                        </a>
                        <a class="btn btn-secondary" href="#features">
                            Learn More
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <polyline points="19 12 12 19 5 12"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="hero-visual" aria-hidden="true">
                    {{-- Custom SVG healthcare dashboard illustration --}}
                    <svg class="hero-illustration" viewBox="0 0 480 400" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Healthcare dashboard illustration">
                        <defs>
                            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#2563EB;stop-opacity:0.15"/>
                                <stop offset="100%" style="stop-color:#3B82F6;stop-opacity:0.05"/>
                            </linearGradient>
                            <linearGradient id="grad2" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#2563EB"/>
                                <stop offset="100%" style="stop-color:#3B82F6"/>
                            </linearGradient>
                        </defs>
                        {{-- Background card --}}
                        <rect x="40" y="30" width="400" height="340" rx="20" fill="url(#grad1)" stroke="#2563EB" stroke-opacity="0.15" stroke-width="1.5"/>
                        {{-- Dashboard header bar --}}
                        <rect x="60" y="50" width="360" height="40" rx="10" fill="#fff" fill-opacity="0.9"/>
                        <circle cx="80" cy="70" r="6" fill="#EF4444" fill-opacity="0.7"/>
                        <circle cx="98" cy="70" r="6" fill="#F59E0B" fill-opacity="0.7"/>
                        <circle cx="116" cy="70" r="6" fill="#10B981" fill-opacity="0.7"/>
                        <rect x="140" y="62" width="120" height="16" rx="8" fill="#E5E7EB"/>
                        {{-- Stats row --}}
                        <rect x="60" y="110" width="105" height="70" rx="12" fill="#fff" fill-opacity="0.95"/>
                        <rect x="70" y="125" width="40" height="8" rx="4" fill="#2563EB" fill-opacity="0.6"/>
                        <rect x="70" y="145" width="60" height="20" rx="4" fill="#2563EB" fill-opacity="0.2"/>
                        <rect x="175" y="110" width="105" height="70" rx="12" fill="#fff" fill-opacity="0.95"/>
                        <rect x="185" y="125" width="40" height="8" rx="4" fill="#10B981" fill-opacity="0.6"/>
                        <rect x="185" y="145" width="60" height="20" rx="4" fill="#10B981" fill-opacity="0.2"/>
                        <rect x="290" y="110" width="130" height="70" rx="12" fill="#fff" fill-opacity="0.95"/>
                        <rect x="300" y="125" width="40" height="8" rx="4" fill="#3B82F6" fill-opacity="0.6"/>
                        <rect x="300" y="145" width="80" height="20" rx="4" fill="#3B82F6" fill-opacity="0.2"/>
                        {{-- Chart area --}}
                        <rect x="60" y="200" width="220" height="150" rx="12" fill="#fff" fill-opacity="0.95"/>
                        <polyline points="80,310 110,280 140,290 170,250 200,260 230,220 260,240" stroke="url(#grad2)" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="260" cy="240" r="5" fill="#2563EB"/>
                        {{-- Patient list --}}
                        <rect x="300" y="200" width="120" height="150" rx="12" fill="#fff" fill-opacity="0.95"/>
                        <circle cx="325" cy="228" r="12" fill="#2563EB" fill-opacity="0.15"/>
                        <circle cx="325" cy="228" r="6" fill="#2563EB" fill-opacity="0.4"/>
                        <rect x="345" y="222" width="55" height="6" rx="3" fill="#D1D5DB"/>
                        <rect x="345" y="234" width="35" height="5" rx="2.5" fill="#E5E7EB"/>
                        <circle cx="325" cy="268" r="12" fill="#10B981" fill-opacity="0.15"/>
                        <circle cx="325" cy="268" r="6" fill="#10B981" fill-opacity="0.4"/>
                        <rect x="345" y="262" width="55" height="6" rx="3" fill="#D1D5DB"/>
                        <rect x="345" y="274" width="35" height="5" rx="2.5" fill="#E5E7EB"/>
                        <circle cx="325" cy="308" r="12" fill="#3B82F6" fill-opacity="0.15"/>
                        <circle cx="325" cy="308" r="6" fill="#3B82F6" fill-opacity="0.4"/>
                        <rect x="345" y="302" width="55" height="6" rx="3" fill="#D1D5DB"/>
                        <rect x="345" y="314" width="35" height="5" rx="2.5" fill="#E5E7EB"/>
                        {{-- Doctor figure --}}
                        <circle cx="420" cy="320" r="35" fill="url(#grad2)" fill-opacity="0.15"/>
                        <circle cx="420" cy="295" r="18" fill="#2563EB" fill-opacity="0.25"/>
                        <path d="M395 350 Q420 325 445 350 L445 380 L395 380 Z" fill="#2563EB" fill-opacity="0.2"/>
                        <rect x="408" y="275" width="24" height="8" rx="4" fill="#fff" fill-opacity="0.8"/>
                        {{-- Heart pulse icon --}}
                        <path d="M420 60 L425 55 L430 60 L435 55 L440 60 L440 70 Q440 80 420 90 Q400 80 400 70 L400 60 L405 55 L410 60 L415 55 Z" fill="#EF4444" fill-opacity="0.3"/>
                    </svg>
                </div>
            </div>
        </section>

        {{-- Wave separator --}}
        <div class="wave-separator" aria-hidden="true">
            <svg viewBox="0 0 1440 56" preserveAspectRatio="none">
                <path d="M0,32 C360,56 720,8 1080,32 C1260,44 1380,28 1440,32 L1440,56 L0,56 Z"/>
            </svg>
        </div>

        {{-- ============================================================
             FEATURE SECTION
             ============================================================ --}}
        <section class="section" id="features" aria-labelledby="features-heading">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">Features</span>
                    <h2 class="section-title" id="features-heading">Everything You Need to Manage Healthcare</h2>
                    <p class="section-desc">Powerful tools designed for medical administrators, doctors, and staff — all in one unified platform.</p>
                </div>

                <div class="features-grid">
                    <article class="feature-card reveal">
                        <div class="feature-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <h3>Patient Records</h3>
                        <p>Secure centralized patient management with complete medical history and documentation.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="feature-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                        <h3>Medical Certificates</h3>
                        <p>Generate and manage certificates quickly with standardized templates and workflows.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="feature-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                                <polyline points="20 8 23 11 20 14"/>
                            </svg>
                        </div>
                        <h3>Doctor Referrals</h3>
                        <p>Track referrals efficiently between departments and external healthcare providers.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="feature-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <h3>Reports &amp; Analytics</h3>
                        <p>Visualize operational data with comprehensive reports and real-time dashboards.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="feature-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <h3>Secure Authentication</h3>
                        <p>Protected role-based access with Laravel authentication for admin workspace security.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="feature-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </div>
                        <h3>Fast Performance</h3>
                        <p>Optimized Laravel backend delivering quick response times for daily clinical operations.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- ============================================================
             SYSTEM MODULES
             ============================================================ --}}
        <section class="section" id="modules" aria-labelledby="modules-heading">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">System Modules</span>
                    <h2 class="section-title" id="modules-heading">Integrated Healthcare Modules</h2>
                    <p class="section-desc">Access all clinical and administrative tools from a single unified workspace.</p>
                </div>

                <div class="modules-grid">
                    <div class="module-item reveal">
                        <div class="module-badge" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <div class="module-info">
                            <h4>Patient Management</h4>
                            <p>Registration, profiles, and medical history</p>
                        </div>
                    </div>
                    <div class="module-item reveal">
                        <div class="module-badge" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div class="module-info">
                            <h4>Medical Certificates</h4>
                            <p>Issue and track certificate documents</p>
                        </div>
                    </div>
                    <div class="module-item reveal">
                        <div class="module-badge" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                        </div>
                        <div class="module-info">
                            <h4>Referral System</h4>
                            <p>Inter-department and external referrals</p>
                        </div>
                    </div>
                    <div class="module-item reveal">
                        <div class="module-badge" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </div>
                        <div class="module-info">
                            <h4>Consultations</h4>
                            <p>Clinical visit records and notes</p>
                        </div>
                    </div>
                    <div class="module-item reveal">
                        <div class="module-badge" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        </div>
                        <div class="module-info">
                            <h4>Reports Dashboard</h4>
                            <p>Analytics and operational insights</p>
                        </div>
                    </div>
                    <div class="module-item reveal">
                        <div class="module-badge" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </div>
                        <div class="module-info">
                            <h4>Admin Workspace</h4>
                            <p>Role-based administration at /admin/workspace</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================
             WHY CHOOSE US
             ============================================================ --}}
        <section class="section why-section" aria-labelledby="why-heading">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">Why Choose Us</span>
                    <h2 class="section-title" id="why-heading">Built for Modern Healthcare Teams</h2>
                    <p class="section-desc">A platform designed with security, speed, and usability at its core.</p>
                </div>

                <div class="why-grid">
                    <div class="why-item reveal">
                        <div class="why-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Secure — Laravel-protected authentication</span>
                    </div>
                    <div class="why-item reveal">
                        <div class="why-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Fast — Optimized PHP &amp; Laravel backend</span>
                    </div>
                    <div class="why-item reveal">
                        <div class="why-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Responsive — Works on all devices</span>
                    </div>
                    <div class="why-item reveal">
                        <div class="why-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Easy to Use — Intuitive interface design</span>
                    </div>
                    <div class="why-item reveal">
                        <div class="why-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Modern Interface — Clean SaaS aesthetic</span>
                    </div>
                    <div class="why-item reveal">
                        <div class="why-check" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Role-Based Access — Granular permissions</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================
             CTA BANNER
             ============================================================ --}}
        <section class="section" aria-labelledby="cta-heading">
            <div class="container">
                <div class="cta-banner reveal">
                    <h2 id="cta-heading">{{ $isAuthenticated ? __('Continue to your dashboard') : __('Ready to get started?') }}</h2>
                    <p>{{ $isAuthenticated ? __('You are signed in. Go to your role-based dashboard to manage your health services.') : __('Sign in or create a patient account to book consultations and manage your health services.') }}</p>
                    @if ($isAuthenticated)
                        <a class="btn btn-primary" href="{{ $authRoles->dashboardUrl() }}">{{ $authRoles->dashboardLabel() }}</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('login') }}">{{ __('Login / Register') }}</a>
                    @endif
                </div>
            </div>
        </section>

    </main>

    {{-- ============================================================
         FOOTER
         ============================================================ --}}
    <footer class="site-footer" id="contact" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">{{ config('app.name') }}</div>
                    <p class="footer-tagline">Modern healthcare management for patients, certificates, referrals, and administrative workflows.</p>
                </div>
                <div class="footer-links">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#modules">Modules</a></li>
                        <li><a href="{{ route('login') }}">{{ __('Login / Register') }}</a></li>
                        <li><a href="{{ route('portal.physicians') }}">{{ __('Physicians') }}</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h5>Legal</h5>
                    <ul>
                        <li><a href="#contact">Privacy Policy</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#contact">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                <span class="footer-version">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Version 2.0
                </span>
            </div>
        </div>
    </footer>

    {{-- Back to top --}}
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

    {{-- Minimal JavaScript: scroll reveal, stat counters, back-to-top --}}
    <script>
        (function () {
            'use strict';

            /* Scroll reveal */
            var revealEls = document.querySelectorAll('.reveal');
            if ('IntersectionObserver' in window) {
                var revealObs = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            revealObs.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
                revealEls.forEach(function (el) { revealObs.observe(el); });
            } else {
                revealEls.forEach(function (el) { el.classList.add('visible'); });
            }

            /* Back to top */
            var backBtn = document.getElementById('backToTop');
            if (backBtn) {
                window.addEventListener('scroll', function () {
                    backBtn.classList.toggle('visible', window.scrollY > 400);
                }, { passive: true });

                backBtn.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        })();
    </script>
</body>
</html>
