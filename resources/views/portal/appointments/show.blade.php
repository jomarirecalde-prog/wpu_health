@extends('layouts.portal-app')
@section('title', 'Appointment Details')
@section('page-heading', 'Appointment Details')
@section('page-description', $appointment->appointment_number)
@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calendar-check"></i> Consultation Appointment</h2>
        <x-appointment-status :status="$appointment->status" />
    </div>
    <div class="card-body">
        <div class="settings-summary-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
            <div class="settings-summary-item"><span class="settings-summary-item__label">Date</span><span class="settings-summary-item__value">{{ $appointment->appointment_date->format('F j, Y') }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Time</span><span class="settings-summary-item__value">{{ $appointment->formattedTimeRange() }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Physician</span><span class="settings-summary-item__value">{{ $appointment->physician->displayName() }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Consultation Type</span><span class="settings-summary-item__value">{{ config('appointments.consultation_types.'.$appointment->consultation_type, ucfirst($appointment->consultation_type)) }}</span></div>
        </div>
        <p style="margin-top:16px"><strong>Reason:</strong> {{ $appointment->reason }}</p>
        @if($appointment->notes)<p style="margin-top:8px"><strong>Notes:</strong> {{ $appointment->notes }}</p>@endif
        <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
            <a href="{{ route('portal.appointments') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Appointments</a>
            @if(in_array($appointment->status->value, ['pending','confirmed']))
            <button type="button" class="btn btn-danger btn-sm" onclick="cancelAppt()"><i class="fas fa-ban"></i> Cancel Appointment</button>
            @endif
        </div>
    </div>
</div>

@if($appointment->history->isNotEmpty())
<div class="content-card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-history"></i> History</h2></div>
    <div class="card-body">
        <ul style="margin:0;padding-left:1.25rem;font-size:13px">
            @foreach($appointment->history as $h)
            <li style="margin-bottom:4px">{{ $h->action }} — {{ $h->changed_at->format('M j, Y g:i A') }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif
@endsection
@push('scripts')
<script>
async function cancelAppt() {
    if (!confirm('Cancel this appointment?')) return;
    const res = await fetch(`/api/appointments/{{ $appointment->id }}/cancel`, { method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({reason:'Cancelled by patient'})});
    if (res.ok) { typeof AlertSystem!=='undefined' ? AlertSystem.toast('Appointment cancelled.','success') : alert('Cancelled'); location.reload(); }
    else { typeof AlertSystem!=='undefined' ? AlertSystem.toast('Cancellation not allowed.','error') : alert('Failed'); }
}
</script>
@endpush
