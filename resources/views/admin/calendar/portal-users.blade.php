@extends('layouts.his-admin')
@section('title', 'Portal Users')
@section('page-heading', 'Portal Users')
@section('page-description', 'Patient accounts registered for consultation booking.')
@php $activeSection = 'portal-users'; @endphp
@section('content')
<div class="content-card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-users"></i> Registered Users</h2></div>
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Student/Employee ID</th><th>Status</th><th>Registered</th></tr></thead>
                <tbody>@foreach($users as $u)<tr>
                    <td data-label="Name">{{ $u->name }}</td><td data-label="Email">{{ $u->email }}</td><td data-label="ID">{{ $u->employee_student_id ?? '—' }}</td>
                    <td data-label="Status"><span class="status-badge {{ $u->status==='active'?'status-badge--completed':'status-badge--cancelled' }}">{{ ucfirst($u->status) }}</span></td>
                    <td data-label="Registered">{{ $u->created_at->format('M j, Y') }}</td>
                </tr>@endforeach</tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</div>
@endsection
