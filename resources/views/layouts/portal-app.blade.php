<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base" content="{{ rtrim(url('/'), '/') }}">
    <title>@yield('title') — WPU Health Services</title>
    <link rel="icon" type="image/png" href="{{ $wpuAssets }}/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/style.css?v={{ $wpuStyleCssV }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/admin-core.css?v={{ $wpuCoreCssV }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/admin-his.css?v={{ filemtime(base_path('unified_portal/assets/css/admin-his.css')) }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/admin-calendar.css?v={{ filemtime(base_path('unified_portal/assets/css/admin-calendar.css')) }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/portal-shell.css?v={{ $wpuPortalShellCssV }}">
    @stack('styles')
</head>
<body class="portal-body">
@include('partials.portal-public-header')
<div class="portal-shell">
    @include($navPartial ?? 'partials.portal-nav')
    <div class="portal-main">
        <div class="alert-container" style="position:relative;top:0;right:0;margin-bottom:12px">
            @if (session('status'))<div class="alert alert-success"><i class="fas fa-check-circle alert-icon"></i><div class="alert-content"><div class="alert-message">{{ session('status') }}</div></div></div>@endif
            @if ($errors->any())<div class="alert alert-error"><i class="fas fa-circle-exclamation alert-icon"></i><div class="alert-content"><div class="alert-message">{{ $errors->first() }}</div></div></div>@endif
        </div>
        <h1 class="portal-page-title">@yield('page-heading', View::yieldContent('title'))</h1>
        @yield('content')
    </div>
</div>
<script src="{{ $wpuAssets }}/js/admin-core.js?v={{ $wpuCoreJsV }}"></script>
@stack('scripts')
</body>
</html>
