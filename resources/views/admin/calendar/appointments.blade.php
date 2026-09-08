@extends('layouts.his-admin')
@section('title', 'Appointments')
@section('page-heading', 'Appointments')
@section('page-description', 'View and filter all consultation appointments.')
@php $activeSection = 'appointments'; @endphp
@section('content')
<div class="content-card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-list"></i> Appointment List</h2></div>
    <div class="search-section cr-toolbar">
        <form method="get" class="cr-search-row" style="display:flex;flex-wrap:wrap;gap:12px;width:100%;align-items:flex-end">
            <div class="form-group" style="margin:0"><label>Physician</label><select name="physician_id" class="form-control"><option value="">All Physicians</option>@foreach($physicians as $p)<option value="{{ $p->id }}" @selected(request('physician_id')==$p->id)>{{ $p->displayName() }}</option>@endforeach</select></div>
            <div class="form-group" style="margin:0"><label>Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['pending','confirmed','completed','cancelled','rejected','no_show'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
            <div class="form-group" style="margin:0"><label>From</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="form-group" style="margin:0"><label>To</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Appointment #</th><th>Date</th><th>Time</th><th>Patient</th><th>Physician</th><th>Consultation</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($appointments as $apt)
                <tr>
                    <td data-label="Appointment #">{{ $apt->appointment_number }}</td>
                    <td data-label="Date">{{ $apt->appointment_date->format('M j, Y') }}</td>
                    <td data-label="Time">{{ $apt->formattedTimeRange() }}</td>
                    <td data-label="Patient">{{ $apt->portalUser->name }}</td>
                    <td data-label="Physician">{{ $apt->physician->displayName() }}</td>
                    <td data-label="Consultation">{{ $apt->consultation_type }}</td>
                    <td data-label="Status"><x-appointment-status :status="$apt->status" /></td>
                </tr>
                @empty<tr><td colspan="7"><div class="table-empty"><p class="table-empty__title">No appointments found</p></div></td></tr>@endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())<div class="pagination">{{ $appointments->links() }}</div>@endif
    </div>
</div>
@endsection
