@extends('layouts.physician-app')
@section('title', 'My Appointments')
@section('page-heading', 'My Appointments')
@section('page-description', 'Manage patient consultation appointments.')
@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-list"></i> Appointments</h2>
    </div>
    <div class="card-body" style="padding:0">
        <div class="calendar-filters" style="padding:12px 16px;border-bottom:1px solid var(--his-border,#E2E8F0)">
            <form method="get">
                <div class="form-group" style="margin:0">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control" style="max-width:200px" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        @foreach(['pending','confirmed','completed','cancelled'] as $s)
                        <option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Status</th><th></th></tr></thead>
                <tbody>@foreach($appointments as $a)<tr>
                    <td>{{ $a->appointment_date->format('M j, Y') }}</td>
                    <td>{{ $a->formattedTimeRange() }}</td>
                    <td>{{ $a->portalUser->name }}</td>
                    <td><x-appointment-status :status="$a->status" /></td>
                    <td><a href="{{ route('physician.appointments.show', $a) }}" class="btn btn-secondary btn-sm">Manage</a></td>
                </tr>@endforeach</tbody>
            </table>
        </div>
        @if($appointments->hasPages())
        <div style="padding:12px 16px">{{ $appointments->links() }}</div>
        @endif
    </div>
</div>
@endsection
