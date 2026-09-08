@extends('layouts.portal-app')
@section('title', 'Book Consultation')
@section('page-heading', 'Book Consultation')
@section('page-description', 'Schedule a consultation with an available physician.')
@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calendar-plus"></i> Booking Wizard</h2>
    </div>
    <div class="card-body">
        <form id="booking-form" method="post" action="{{ route('portal.book.store') }}">@csrf
            <div class="step" data-step="1">
                <h3 style="font-size:14px;margin-bottom:12px;font-weight:600">Step 1 — Choose Physician</h3>
                <div class="form-group"><label for="physician_id">Physician</label><select name="physician_id" id="physician_id" class="form-control" required><option value="">— Select Physician —</option>@foreach($physicians as $p)<option value="{{ $p->id }}">{{ $p->displayName() }} @if($p->specialization)({{ $p->specialization }})@endif</option>@endforeach</select></div>
            </div>
            <div class="step" data-step="2" hidden>
                <h3 style="font-size:14px;margin-bottom:12px;font-weight:600">Step 2 — Consultation Type</h3>
                <div class="form-group"><label for="consultation_type">Consultation Type</label><select name="consultation_type" id="consultation_type" class="form-control" required>@foreach($consultationTypes as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            </div>
            <div class="step" data-step="3" hidden>
                <h3 style="font-size:14px;margin-bottom:12px;font-weight:600">Step 3 — Choose Date</h3>
                <div class="form-group"><label for="appointment_date">Date</label><input type="date" id="appointment_date" name="appointment_date" class="form-control" min="{{ date('Y-m-d') }}" required></div>
            </div>
            <div class="step" data-step="4" hidden>
                <h3 style="font-size:14px;margin-bottom:12px;font-weight:600">Step 4 — Choose Time</h3>
                <div id="slots-container" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px"></div>
                <input type="hidden" name="start_time" id="start_time" required>
            </div>
            <div class="step" data-step="5" hidden>
                <h3 style="font-size:14px;margin-bottom:12px;font-weight:600">Step 5 — Reason</h3>
                <div class="form-group"><label for="reason">Reason for Consultation</label><textarea name="reason" id="reason" class="form-control" rows="4" required placeholder="Describe your reason for consultation"></textarea></div>
            </div>
            <div class="step" data-step="6" hidden>
                <h3 style="font-size:14px;margin-bottom:12px;font-weight:600">Step 6 — Review &amp; Confirm</h3>
                <div id="review-summary" class="settings-summary-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px"></div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="button" class="btn btn-secondary" id="prev-btn" hidden>Back</button>
                <button type="button" class="btn btn-primary" id="next-btn">Next</button>
                <button type="submit" class="btn btn-primary" id="submit-btn" hidden><i class="fas fa-check"></i> Confirm Booking</button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
(() => {
    let step = 1; const total = 6; const steps = document.querySelectorAll('.step');
    const prev = document.getElementById('prev-btn'), next = document.getElementById('next-btn'), submit = document.getElementById('submit-btn');
    function show(s) { step = s; steps.forEach(el => el.hidden = +el.dataset.step !== s); prev.hidden = s === 1; next.hidden = s === total; submit.hidden = s !== total; if (s === 6) updateReview(); }
    prev.onclick = () => show(step - 1);
    next.onclick = async () => { if (step === 3) await loadSlots(); if (step < total) show(step + 1); };
    async function loadSlots() {
        const box = document.getElementById('slots-container'); box.innerHTML = '<span class="status-badge">Loading…</span>';
        const res = await fetch(`{{ route('portal.book.slots') }}?physician_id=${document.getElementById('physician_id').value}&date=${document.getElementById('appointment_date').value}`);
        const data = await res.json(); box.innerHTML = '';
        if (!data.slots.length) { box.innerHTML = '<div class="alert alert-warning" style="margin:0"><i class="fas fa-exclamation-triangle alert-icon"></i><div class="alert-content"><div class="alert-message">No available slots for this date.</div></div></div>'; return; }
        data.slots.forEach(s => { const b = document.createElement('button'); b.type = 'button'; b.className = 'btn btn-secondary btn-sm'; b.textContent = s.label; b.onclick = () => { document.querySelectorAll('#slots-container button').forEach(x => x.className = 'btn btn-secondary btn-sm'); b.className = 'btn btn-primary btn-sm'; document.getElementById('start_time').value = s.start; }; box.appendChild(b); });
    }
    function updateReview() {
        const f = document.getElementById('booking-form');
        document.getElementById('review-summary').innerHTML = `
            <div class="settings-summary-item"><span class="settings-summary-item__label">Physician</span><span class="settings-summary-item__value">${f.physician_id.selectedOptions[0]?.text || '—'}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Type</span><span class="settings-summary-item__value">${f.consultation_type.value}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Date</span><span class="settings-summary-item__value">${f.appointment_date.value}</span></div>
            <div class="settings-summary-item"><span class="settings-summary-item__label">Time</span><span class="settings-summary-item__value">${f.start_time.value || '—'}</span></div>
            <div class="settings-summary-item" style="grid-column:1/-1"><span class="settings-summary-item__label">Reason</span><span class="settings-summary-item__value">${f.reason.value}</span></div>`;
    }
})();
</script>
@endpush
