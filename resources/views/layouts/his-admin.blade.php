<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base" content="{{ rtrim(url('/'), '/') }}">
    <title>@yield('title', 'Admin') — WPU Health Services</title>
    <link rel="icon" type="image/png" href="{{ $wpuAssets }}/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script>
    (function(){try{var t=localStorage.getItem('wpu_his_theme');if(!t&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)t='dark';if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();
    </script>
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/admin-core.css?v={{ $wpuCoreCssV }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/admin-his.css?v={{ $wpuHisCssV }}">
    <link rel="stylesheet" href="{{ $wpuAssets }}/css/admin-calendar.css?v={{ $wpuCalendarCssV }}">
    @stack('styles')
    <style>
    .fa,.fas,.far,.fab,.fa-solid,.fa-regular,.fa-brands,.fa::before,.fas::before,i.fas,i.far,i.fab{font-family:"Font Awesome 6 Free"!important;font-weight:900!important;font-style:normal!important;}
    .fab,.fa-brands{font-family:"Font Awesome 6 Brands"!important;font-weight:400!important;}
    </style>
</head>
<body>
@include('partials.his-sidebar')
<div class="sidebar-backdrop no-print" id="sidebar-backdrop" onclick="toggleSidebar()" aria-hidden="true"></div>
<div class="main-content">
    @include('partials.his-topbar')
    <div class="container">
        <div class="alert-container" id="alert-container">
            @if (session('status'))
                <div class="alert alert-success"><i class="fas fa-check-circle alert-icon"></i><div class="alert-content"><div class="alert-title">Success</div><div class="alert-message">{{ session('status') }}</div></div><button type="button" class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div>
            @endif
            @if (session('error'))
                <div class="alert alert-error"><i class="fas fa-circle-exclamation alert-icon"></i><div class="alert-content"><div class="alert-title">Error</div><div class="alert-message">{{ session('error') }}</div></div><button type="button" class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error"><i class="fas fa-circle-exclamation alert-icon"></i><div class="alert-content"><div class="alert-title">Validation</div><div class="alert-message"><ul style="margin:0;padding-left:1rem">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div><button type="button" class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div>
            @endif
        </div>
        <div class="header">
            <div class="breadcrumbs"><a href="{{ route('admin.workspace', ['path' => 'admin/admin.php']) }}">Dashboard</a> <span>/</span> <span>@yield('title')</span></div>
            <div class="medical-header">
                <h1>@yield('page-heading', View::yieldContent('title'))</h1>
                @hasSection('page-description')<p class="page-header-meta">@yield('page-description')</p>@endif
            </div>
        </div>
        <div class="content">@yield('content')</div>
    </div>
</div>
<script src="{{ $wpuAssets }}/js/admin-core.js?v={{ $wpuCoreJsV }}"></script>
<script src="{{ $wpuAssets }}/js/wpu-ajax.js?v={{ $wpuAjaxJsV }}"></script>
<script src="{{ $wpuAssets }}/js/admin-his-ui.js?v={{ $wpuHisJsV }}"></script>
@stack('scripts')
</body>
</html>
