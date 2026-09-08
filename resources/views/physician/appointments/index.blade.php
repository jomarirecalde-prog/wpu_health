@extends('layouts.physician-app')
@section('title', 'My Appointments')
@section('page-heading', 'My Appointments')
@section('content')
<div class="portal-card"><div class="portal-card__body" style="padding:0">
    <div class="search-section" style="padding:12px 16px"><form method="get"><select name="status" class="form-control" style="max-width:200px;display:inline-block" onchange="this.form.submit()"><option value="">All Status</option>@foreach(['pending','confirmed','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>@endforeach</select></form></div>
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($appointments as $a)<tr>
            <td>{{ $a->appointment_date->format('M j, Y') }}</td><td>{{ $a->formattedTimeRange() }}</td><td>{{ $a->portalUser->name }}</td>
            <td><x-appointment-status :status="$a->status" /></td>
            <td><a href="{{ route('physician.appointments.show', $a) }}" class="btn btn-secondary btn-sm">Manage</a></td>
        </tr>@endforeach</tbody>
    </table></div>
    {{ $appointments->links() }}
</div></div>
@endsection
