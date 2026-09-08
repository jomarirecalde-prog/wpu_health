@extends('layouts.portal-app')
@section('title', 'Appointment Details')
@section('page-heading', 'Appointment Confirmation')
@section('content')
<div class="portal-card">
    <div class="portal-card__head"><i class="fas fa-check-circle"></i> {{ $appointment->appointment_number }}</div>
    <div class="portal-card__body">
        @if(session('status'))<div class="alert alert-success"><i class="fas fa-check-circle alert-icon"></i><div class="alert-content"><div class="alert-message">{{ session('status') }}</div></div></div>@endif
        <div class="settings-summary-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
            <div class="settings-summary-item"><span class="settings-summary-item__label">Date</span><span class="settings-summary-item__value">{{ $appointment->appointment_date->format('F j, Y') }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Time</span><span class="settings-summary-item__value">{{ $appointment->formattedTimeRange() }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Physician</span><span class="settings-summary-item__value">{{ $appointment->physician->displayName() }}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Status</span><span class="settings-summary-item__value"><x-appointment-status :status="$appointment->status" /></span></div>
        </div>
        <p style="margin-top:12px"><strong>Reason:</strong> {{ $appointment->reason }}</p>
        @if(in_array($appointment->status->value, ['pending','confirmed']))
        <button type="button" class="btn btn-danger btn-sm" style="margin-top:12px" onclick="cancelAppt()"><i class="fas fa-ban"></i> Cancel Appointment</button>
        @endif
    </div>
</div>
@if($appointment->history->isNotEmpty())
<div class="portal-card"><div class="portal-card__head">History</div><div class="portal-card__body"><ul style="margin:0;padding-left:1.25rem;font-size:13px">@foreach($appointment->history as $h)<li style="margin-bottom:4px">{{ $h->action }} — {{ $h->changed_at->format('M j, Y g:i A') }}</li>@endforeach</ul></div></div>
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
