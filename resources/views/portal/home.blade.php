@extends('layouts.portal-public')
@section('title', 'WPU Health Services')
@section('content')
@php $authRoles = app(\App\Services\AuthRoleService::class); @endphp
<div class="portal-welcome">
    <h2>WPU Health Services</h2>
    <p>Book consultations, manage appointments, and connect with university physicians.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:16px">
        @if ($authRoles->isAuthenticated())
            <a href="{{ $authRoles->dashboardUrl() }}" class="btn btn-primary"><i class="fas fa-th-large"></i> {{ $authRoles->dashboardLabel() }}</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login / Register</a>
        @endif
        <a href="{{ route('portal.physicians') }}" class="btn btn-secondary"><i class="fas fa-user-md"></i> Available Physicians</a>
        <a href="{{ route('home') }}" class="btn btn-secondary"><i class="fas fa-home"></i> Main Site</a>
    </div>
</div>
<div class="panels-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
    <div class="panel-card"><div class="panel-header"><div class="panel-icon"><i class="fas fa-calendar-plus"></i></div><div class="panel-title">How to Book</div></div><div class="panel-body"><ul class="panel-features"><li>Create an account or login</li><li>Select a physician</li><li>Choose date and time</li><li>Confirm your appointment</li></ul></div></div>
    <div class="panel-card"><div class="panel-header"><div class="panel-icon"><i class="fas fa-hospital"></i></div><div class="panel-title">Clinic Information</div></div><div class="panel-body"><p>Western Philippines University Health Services provides medical and dental consultations for students, employees, and authorized personnel.</p></div></div>
</div>
@endsection
