@extends('layouts.his-admin')
@section('title', 'Patient Details')
@section('page-heading', 'Patient Details')
@section('page-description', 'Read-only account information for this portal user.')
@php $activeSection = 'portal-users'; @endphp

@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user"></i> Patient Information</h2>
        <div class="table-actions">
            <a href="{{ route('admin.calendar.portal-users') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
            <a href="{{ route('admin.calendar.portal-users.edit', $user) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit Patient</a>
        </div>
    </div>
    <div class="card-body">
        <div class="portal-user-profile-head">
            <x-portal-user-avatar :user="$user" size="profile" />
            <div>
                <h3 class="portal-user-profile-head__name">{{ $user->name }}</h3>
                <p class="portal-user-profile-head__meta">{{ $user->email }}</p>
                <span class="status-badge {{ $user->statusBadgeClass() }}">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    {{ ucfirst($user->status) }}
                </span>
            </div>
        </div>

        <h3 class="portal-user-section-title">Personal Information</h3>
        <div class="settings-summary-grid">
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Full Name</span>
                <span class="settings-summary-item__value">{{ $user->name }}</span>
            </div>
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Email</span>
                <span class="settings-summary-item__value">{{ $user->email }}</span>
            </div>
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Contact Number</span>
                <span class="settings-summary-item__value">{{ $user->contact_number ?: '—' }}</span>
            </div>
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Date of Birth</span>
                <span class="settings-summary-item__value">{{ $user->date_of_birth?->format('F j, Y') ?: '—' }}</span>
            </div>
            <div class="settings-summary-item" style="grid-column:1/-1">
                <span class="settings-summary-item__label">Address</span>
                <span class="settings-summary-item__value">{{ $user->address ?: '—' }}</span>
            </div>
        </div>

        <h3 class="portal-user-section-title">Account Information</h3>
        <div class="settings-summary-grid">
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Patient Type</span>
                <span class="settings-summary-item__value">{{ $user->patientType?->type_name ?? '—' }}</span>
            </div>
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Department</span>
                <span class="settings-summary-item__value">{{ $user->department?->name ?? '—' }}</span>
            </div>
            <div class="settings-summary-item settings-summary-item--mono">
                <span class="settings-summary-item__label">Student/Employee ID</span>
                <span class="settings-summary-item__value">{{ $user->employee_student_id ?: '—' }}</span>
            </div>
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Account Status</span>
                <span class="settings-summary-item__value">
                    <span class="status-badge {{ $user->statusBadgeClass() }}">
                        <i class="fas fa-circle" aria-hidden="true"></i>
                        {{ ucfirst($user->status) }}
                    </span>
                </span>
            </div>
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Registered Date</span>
                <span class="settings-summary-item__value">{{ $user->created_at?->format('F j, Y g:i A') ?: '—' }}</span>
            </div>
            <div class="settings-summary-item">
                <span class="settings-summary-item__label">Last Updated</span>
                <span class="settings-summary-item__value">{{ $user->updated_at?->format('F j, Y g:i A') ?: '—' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.portal-user-profile-head { display:flex; align-items:center; gap:18px; margin-bottom:24px; flex-wrap:wrap; }
.portal-user-profile-head__name { margin:0 0 4px; font-size:1.25rem; color:var(--primary-blue, #1a5f7a); }
.portal-user-profile-head__meta { margin:0 0 8px; color:var(--gray-600, #64748b); font-size:14px; }
.portal-user-section-title { margin:24px 0 12px; font-size:15px; font-weight:700; color:var(--gray-700, #334155); }
.table-actions { display:flex; gap:8px; flex-wrap:wrap; }
</style>
@endpush
