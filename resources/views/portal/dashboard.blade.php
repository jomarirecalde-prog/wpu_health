@extends('layouts.portal-app')
@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')
@section('content')
<div class="portal-stat-grid">
    <div class="portal-stat"><div class="portal-stat__label">Next Appointment</div><div class="portal-stat__value" style="font-size:14px">@if($nextAppointment){{ $nextAppointment->appointment_date->format('M j, Y') }} · {{ $nextAppointment->formattedTimeRange() }}@else—@endif</div></div>
    <div class="portal-stat"><div class="portal-stat__label">Status</div><div class="portal-stat__value" style="font-size:14px">@if($nextAppointment)<x-appointment-status :status="$nextAppointment->status" />@else No upcoming @endif</div></div>
    <div class="portal-stat"><div class="portal-stat__label">Notifications</div><div class="portal-stat__value">{{ $unreadCount }} unread</div></div>
</div>
<p style="margin-bottom:16px"><a href="{{ route('portal.book') }}" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Quick Book Consultation</a></p>
<div class="portal-card">
    <div class="portal-card__head"><i class="fas fa-history"></i> Recent Appointments</div>
    <div class="portal-card__body" style="padding:0">
        <div class="table-responsive"><table class="data-table">
            <thead><tr><th>Date</th><th>Physician</th><th>Status</th><th></th></tr></thead>
            <tbody>@forelse($recentAppointments as $apt)<tr>
                <td>{{ $apt->appointment_date->format('M j, Y') }}</td><td>{{ $apt->physician->displayName() }}</td>
                <td><x-appointment-status :status="$apt->status" /></td>
                <td><a href="{{ route('portal.appointments.show', $apt) }}" class="btn btn-secondary btn-sm">View</a></td>
            </tr>@empty<tr><td colspan="4" class="table-empty"><p class="table-empty__title">No appointments yet</p></td></tr>@endforelse</tbody>
        </table></div>
    </div>
</div>
@endsection
