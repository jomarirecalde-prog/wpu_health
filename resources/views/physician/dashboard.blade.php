@extends('layouts.physician-app')
@section('title', 'Dashboard')
@section('page-heading', 'Physician Dashboard')
@section('page-description', 'Today\'s consultations and upcoming appointments.')
@section('content')
<div class="stats-grid dashboard-stats">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $todayAppointments->count() }}</div>
                <div class="stat-label">Today's Consultations</div>
            </div>
            <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $upcoming->count() }}</div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calendar-day"></i> Today's Appointments</h2>
        <a href="{{ route('physician.appointments') }}" class="btn btn-secondary btn-sm"><i class="fas fa-list"></i> View All</a>
    </div>
    <div class="card-body">
        @forelse($todayAppointments as $a)
        <div class="recent-row" style="padding:10px 0;border-bottom:1px solid var(--his-border,#E2E8F0);display:flex;justify-content:space-between;align-items:center;gap:12px">
            <div class="meta"><strong>{{ $a->portalUser->name }}</strong><span style="display:block;font-size:13px;color:var(--his-text-muted,#64748B)">{{ $a->formattedTimeRange() }}</span></div>
            <div style="display:flex;align-items:center;gap:8px">
                <x-appointment-status :status="$a->status" />
                <a href="{{ route('physician.appointments.show', $a) }}" class="btn btn-secondary btn-sm">Manage</a>
            </div>
        </div>
        @empty
        <div class="table-empty"><p class="table-empty__title">No appointments today.</p></div>
        @endforelse
    </div>
</div>
@endsection
