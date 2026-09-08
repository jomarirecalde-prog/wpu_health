@extends('layouts.portal-app')
@section('title', 'My Appointments')
@section('page-heading', 'My Appointments')
@section('page-description', 'View and manage your consultation appointments.')
@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-list"></i> Appointments</h2>
        <a href="{{ route('portal.book') }}" class="btn btn-primary btn-sm"><i class="fas fa-calendar-plus"></i> Book Consultation</a>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Appointment #</th><th>Date</th><th>Time</th><th>Physician</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse($appointments as $apt)<tr>
                    <td>{{ $apt->appointment_number }}</td>
                    <td>{{ $apt->appointment_date->format('M j, Y') }}</td>
                    <td>{{ $apt->formattedTimeRange() }}</td>
                    <td>{{ $apt->physician->displayName() }}</td>
                    <td><x-appointment-status :status="$apt->status" /></td>
                    <td><a href="{{ route('portal.appointments.show', $apt) }}" class="btn btn-secondary btn-sm">Details</a></td>
                </tr>@empty<tr><td colspan="6"><div class="table-empty"><p class="table-empty__title">No appointments found</p></div></td></tr>@endforelse</tbody>
            </table>
        </div>
        @if($appointments->hasPages())
        <div style="padding:12px 16px">{{ $appointments->links() }}</div>
        @endif
    </div>
</div>
@endsection
