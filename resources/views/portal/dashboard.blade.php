@extends('layouts.portal-app')
@section('title', 'Dashboard')
@section('page-heading', 'Welcome, ' . auth('portal')->user()->name)
@section('page-description', 'Your consultation overview and upcoming appointments.')
@section('content')
<div class="stats-grid dashboard-stats">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value" style="font-size:16px">@if($nextAppointment){{ $nextAppointment->appointment_date->format('M j, Y') }}@else—@endif</div>
                <div class="stat-label">Next Appointment</div>
                @if($nextAppointment)<div style="font-size:12px;color:var(--his-text-muted,#64748B);margin-top:4px">{{ $nextAppointment->formattedTimeRange() }}</div>@endif
            </div>
            <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value" style="font-size:14px">@if($nextAppointment)<x-appointment-status :status="$nextAppointment->status" />@else No upcoming @endif</div>
                <div class="stat-label">Status</div>
            </div>
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $unreadCount }}</div>
                <div class="stat-label">Unread Notifications</div>
            </div>
            <div class="stat-icon orange"><i class="fas fa-bell"></i></div>
        </div>
    </div>
</div>

<p style="margin-bottom:18px"><a href="{{ route('portal.book') }}" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Book Consultation</a></p>

@if($nextAppointment)
<div class="content-card" style="margin-bottom:18px">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calendar-check"></i> Upcoming Appointment</h2>
        <a href="{{ route('portal.appointments.show', $nextAppointment) }}" class="btn btn-secondary btn-sm">View Appointment</a>
    </div>
    <div class="card-body">
        <div class="settings-summary-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
            <div class="settings-summary-item"><span class="settings-summary-item__label">Date</span><span class="settings-summary-item__value">{{ $nextAppointment->appointment_date->format('F j, Y') }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Time</span><span class="settings-summary-item__value">{{ $nextAppointment->formattedTimeRange() }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Physician</span><span class="settings-summary-item__value">{{ $nextAppointment->physician->displayName() }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Consultation Type</span><span class="settings-summary-item__value">{{ config('appointments.consultation_types.'.$nextAppointment->consultation_type, ucfirst($nextAppointment->consultation_type)) }}</span></div>
        </div>
    </div>
</div>
@endif

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-history"></i> Recent Appointments</h2>
        <a href="{{ route('portal.appointments') }}" class="btn btn-secondary btn-sm"><i class="fas fa-list"></i> View All</a>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Physician</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse($recentAppointments as $apt)<tr>
                    <td>{{ $apt->appointment_date->format('M j, Y') }}</td>
                    <td>{{ $apt->physician->displayName() }}</td>
                    <td><x-appointment-status :status="$apt->status" /></td>
                    <td><a href="{{ route('portal.appointments.show', $apt) }}" class="btn btn-secondary btn-sm">View</a></td>
                </tr>@empty<tr><td colspan="4" class="table-empty"><p class="table-empty__title">No appointments yet</p></td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>
</div>
@endsection
