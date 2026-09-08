<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WPU Health Services')</title>
    <link rel="icon" type="image/png" href="{{ $wpuAssets }}/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/style.css?v={{ $wpuStyleCssV }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/admin-core.css?v={{ $wpuCoreCssV }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/portal-shell.css?v={{ $wpuPortalShellCssV }}">
    @stack('styles')
</head>
<body class="portal-body">
@include('partials.portal-public-header')
<main class="admin-main">
    <div class="container">@yield('content')</div>
</main>
<footer class="admin-footer"><div class="container"><p>&copy; {{ date('Y') }} WPU Health Services</p></div></footer>
<script src="{{ $wpuAssets }}/js/admin-core.js?v={{ $wpuCoreJsV }}"></script>
@stack('scripts')
</body>
</html>
