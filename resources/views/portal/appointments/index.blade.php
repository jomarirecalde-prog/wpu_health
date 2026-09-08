@extends('layouts.portal-app')
@section('title', 'My Appointments')
@section('page-heading', 'My Appointments')
@section('content')
<div class="portal-card"><div class="portal-card__body" style="padding:0">
    <div class="table-responsive"><table class="data-table">
        <thead><tr><th>Appointment #</th><th>Date</th><th>Time</th><th>Physician</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($appointments as $apt)<tr>
            <td>{{ $apt->appointment_number }}</td><td>{{ $apt->appointment_date->format('M j, Y') }}</td><td>{{ $apt->formattedTimeRange() }}</td>
            <td>{{ $apt->physician->displayName() }}</td><td><x-appointment-status :status="$apt->status" /></td>
            <td><a href="{{ route('portal.appointments.show', $apt) }}" class="btn btn-secondary btn-sm">Details</a></td>
        </tr>@empty<tr><td colspan="6"><div class="table-empty"><p class="table-empty__title">No appointments found</p></div></td></tr>@endforelse</tbody>
    </table></div>
    {{ $appointments->links() }}
</div></div>
@endsection
