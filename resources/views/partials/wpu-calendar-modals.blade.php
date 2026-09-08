{{-- Shared calendar modals — appointment detail, date detail, book consultation --}}
<div id="appointmentDetailModal" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-dialog" style="max-width:520px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Appointment Details</h2>
                <button type="button" class="close" onclick="WpuCalendar.closeModal('appointmentDetailModal')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body" id="appointmentDetailBody"></div>
            <div class="modal-footer" id="appointmentDetailActions"></div>
        </div>
    </div>
</div>

<div id="dateDetailModal" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-dialog" style="max-width:560px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Date Details</h2>
                <button type="button" class="close" onclick="WpuCalendar.closeModal('dateDetailModal')" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body" id="dateDetailBody"></div>
            <div class="modal-footer" id="dateDetailFooter"></div>
        </div>
    </div>
</div>

@if($showBookModal ?? false)
<div id="bookConsultationModal" class="modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-dialog" style="max-width:520px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Book Consultation</h2>
                <button type="button" class="close" onclick="WpuCalendar.closeModal('bookConsultationModal')" aria-label="Close">&times;</button>
            </div>
            <form id="bookConsultationForm">
                <div class="modal-body">
                    <p class="wpu-cal-muted" style="margin-bottom:12px">Date: <strong data-book-date-label></strong></p>
                    <input type="hidden" name="appointment_date">
                    <div class="form-group">
                        <label for="book-patient">Patient</label>
                        <select id="book-patient" name="portal_user_id" class="form-control" required>
                            <option value="">Select patient…</option>
                            @foreach($portalUsers ?? [] as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="book-physician">Physician</label>
                        <select id="book-physician" name="physician_id" class="form-control" required>
                            <option value="">Select physician…</option>
                            @foreach($physicians ?? [] as $p)
                                <option value="{{ $p->id }}">{{ $p->displayName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="book-time">Time</label>
                        <input type="time" id="book-time" name="start_time" class="form-control" step="60" required>
                    </div>
                    <div class="form-group">
                        <label for="book-type">Consultation type</label>
                        <select id="book-type" name="consultation_type" class="form-control" required>
                            @foreach($consultationTypes ?? [] as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="book-reason">Reason</label>
                        <textarea id="book-reason" name="reason" class="form-control" rows="2" placeholder="Optional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Book</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="WpuCalendar.closeModal('bookConsultationModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
