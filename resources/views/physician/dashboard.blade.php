@extends('layouts.physician-app')
@section('title', 'Dashboard')
@section('page-heading', 'Physician Dashboard')
@section('content')
<div class="portal-stat-grid">
    <div class="portal-stat"><div class="portal-stat__label">Today's Consultations</div><div class="portal-stat__value">{{ $todayAppointments->count() }}</div></div>
    <div class="portal-stat"><div class="portal-stat__label">Upcoming</div><div class="portal-stat__value">{{ $upcoming->count() }}</div></div>
</div>
<div class="portal-card"><div class="portal-card__head"><i class="fas fa-calendar-day"></i> Today's Appointments</div><div class="portal-card__body">
@forelse($todayAppointments as $a)
<div class="recent-row" style="padding:8px 0;border-bottom:1px solid var(--gray-200)">
    <div class="meta"><strong>{{ $a->portalUser->name }}</strong><span>{{ $a->formattedTimeRange() }}</span></div>
    <x-appointment-status :status="$a->status" />
</div>
@empty<p style="color:var(--gray-500);font-size:13px">No appointments today.</p>@endforelse
</div></div>
@endsection
