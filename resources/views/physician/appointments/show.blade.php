@extends('layouts.physician-app')
@section('title', 'Appointment')
@section('page-heading', $appointment->appointment_number)
@section('content')
<div class="portal-card"><div class="portal-card__body">
    <div class="settings-summary-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
        <div class="settings-summary-item"><span class="settings-summary-item__label">Patient</span><span class="settings-summary-item__value">{{ $appointment->portalUser->name }}</span></div>
        <div class="settings-summary-item"><span class="settings-summary-item__label">Contact</span><span class="settings-summary-item__value">{{ $appointment->portalUser->contact_number }}</span></div>
        <div class="settings-summary-item"><span class="settings-summary-item__label">Date</span><span class="settings-summary-item__value">{{ $appointment->appointment_date->format('F j, Y') }}</span></div>
        <div class="settings-summary-item"><span class="settings-summary-item__label">Status</span><span class="settings-summary-item__value"><x-appointment-status :status="$appointment->status" /></span></div>
    </div>
    <p style="margin-top:12px"><strong>Reason:</strong> {{ $appointment->reason }}</p>
    <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
        @if($appointment->status->value === 'pending')
        <form method="post" action="{{ route('physician.appointments.confirm', $appointment) }}">@csrf<button class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Confirm</button></form>
        <form method="post" action="{{ route('physician.appointments.reject', $appointment) }}" style="display:flex;gap:8px">@csrf<input name="reason" class="form-control" placeholder="Rejection reason" required style="max-width:200px"><button class="btn btn-danger btn-sm">Reject</button></form>
        @endif
        @if($appointment->status->value === 'confirmed')
        <form method="post" action="{{ route('physician.appointments.complete', $appointment) }}">@csrf<button class="btn btn-primary btn-sm"><i class="fas fa-check-double"></i> Mark Completed</button></form>
        <form method="post" action="{{ route('physician.appointments.no-show', $appointment) }}">@csrf<button class="btn btn-secondary btn-sm">No-show</button></form>
        @endif
    </div>
</div></div>
@endsection
