@extends('layouts.his-admin')
@section('title', 'Physicians')
@section('page-heading', 'Physician Management')
@section('page-description', 'Add and manage physicians available for consultation booking.')
@php $activeSection = 'physicians'; @endphp
@section('content')
<div class="content-card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-user-md"></i> Add Physician</h2></div>
    <div class="card-body">
        <form method="post" action="{{ route('admin.calendar.physicians.store') }}" class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">@csrf
            <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
            <div class="form-group"><label>Professional Title</label><input type="text" name="professional_title" class="form-control" placeholder="Dr."></div>
            <div class="form-group"><label>Specialization</label><input type="text" name="specialization" class="form-control"></div>
            <div class="form-group"><label>Duration (min)</label><input type="number" name="consultation_duration" class="form-control" value="30"></div>
            <div class="form-group"><label>Max Daily</label><input type="number" name="max_daily_appointments" class="form-control" value="20"></div>
            <input type="hidden" name="status" value="active">
            <div class="form-group" style="align-self:end"><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Physician</button></div>
        </form>
    </div>
</div>
<div class="content-card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-users"></i> Physicians</h2></div>
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Physician</th><th>Specialization</th><th>Email</th><th>Status</th></tr></thead>
                <tbody>@foreach($physicians as $p)<tr>
                    <td data-label="Physician">{{ $p->displayName() }}</td>
                    <td data-label="Specialization">{{ $p->specialization ?? '—' }}</td>
                    <td data-label="Email">{{ $p->email }}</td>
                    <td data-label="Status"><span class="status-badge {{ $p->status==='active'?'status-badge--completed':'status-badge--cancelled' }}">{{ ucfirst($p->status) }}</span></td>
                </tr>@endforeach</tbody>
            </table>
        </div>
        {{ $physicians->links() }}
    </div>
</div>
@endsection
