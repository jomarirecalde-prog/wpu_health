@extends('layouts.his-admin')
@section('title', 'Appointment Reports')
@section('page-heading', 'Appointment Reports')
@section('page-description', 'Daily, monthly, and status-based consultation reports.')
@php $activeSection = 'reports'; @endphp
@section('content')
<div class="content-card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-chart-line"></i> Generate Report</h2>
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="search-section">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
            <div class="form-group" style="margin:0"><label>From</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="form-group" style="margin:0"><label>To</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="form-group" style="margin:0"><label>Physician</label><select name="physician_id" class="form-control"><option value="">All</option>@foreach($physicians as $p)<option value="{{ $p->id }}" @selected(request('physician_id')==$p->id)>{{ $p->displayName() }}</option>@endforeach</select></div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-chart-bar"></i> Generate</button>
        </form>
    </div>
</div>
<div class="stats-grid dashboard-stats">
    @foreach($summary as $label=>$count)<div class="stat-card"><div class="stat-header"><div><div class="stat-value">{{ $count }}</div><div class="stat-label">{{ ucfirst(str_replace('_',' ',$label)) }}</div></div></div></div>@endforeach
</div>
<div class="content-card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Physician</th><th>Status</th></tr></thead>
                <tbody>@foreach($appointments as $a)<tr>
                    <td>{{ $a->appointment_date->format('M j, Y') }}</td><td>{{ $a->formattedTimeRange() }}</td><td>{{ $a->portalUser->name }}</td><td>{{ $a->physician->displayName() }}</td>
                    <td><x-appointment-status :status="$a->status" /></td>
                </tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</div>
@endsection
