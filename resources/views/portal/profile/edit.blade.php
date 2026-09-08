@extends('layouts.portal-app')
@section('title', 'Profile')
@section('page-heading', 'My Profile')
@section('page-description', 'Manage your account information, profile picture, and password.')

@section('content')
@php
    $accountStatus = $user->status ?? 'active';
    $statusBadgeClass = match ($accountStatus) {
        'active' => 'status-badge--completed',
        'pending' => 'status-badge--pending',
        'suspended' => 'status-badge--rejected',
        'inactive' => 'status-badge--cancelled',
        default => 'status-badge--cancelled',
    };
    $patientTypeLabel = $user->patientType?->type_name ?? '—';
@endphp

<div class="profile-page">

    {{-- Profile Header --}}
    <section class="content-card profile-header-card" aria-label="Profile overview">
        <div class="card-body profile-header">
            <div class="profile-header__avatar-wrap">
                <div class="profile-header__avatar" id="profile-header-avatar">
                    <x-portal-user-avatar :user="$user" size="profile" id="profile-avatar-display" />
                </div>
                <button type="button" class="btn btn-secondary btn-sm profile-header__photo-btn" id="open-photo-modal-btn" onclick="PortalProfile.openPhotoModal()">
                    <i class="fas fa-camera" aria-hidden="true"></i> Change Photo
                </button>
            </div>
            <div class="profile-header__details">
                <h2 class="profile-header__name">{{ $user->name }}</h2>
                <p class="profile-header__role">
                    <i class="fas fa-user-injured" aria-hidden="true"></i>
                    {{ $patientTypeLabel === '—' ? 'Patient' : 'Patient / '.$patientTypeLabel }}
                </p>
                <p class="profile-header__email">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    {{ $user->email }}
                </p>
            </div>
        </div>
    </section>

    {{-- Personal Information --}}
    <section class="content-card profile-info-card" aria-labelledby="personal-info-heading">
        <div class="card-header">
            <h2 class="card-title" id="personal-info-heading"><i class="fas fa-id-card" aria-hidden="true"></i> Personal Information</h2>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('portal.profile.update') }}" class="profile-form" id="profile-form" novalidate>
                @csrf
                @method('PUT')

                <div class="profile-form-grid">
                    <div class="form-group profile-field">
                        <label for="name">Full Name</label>
                        <input name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')<small class="profile-field-error" id="name-error" role="alert">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group profile-field">
                        <label for="contact_number">Contact Number</label>
                        <input name="contact_number" id="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $user->contact_number) }}" required autocomplete="tel">
                        @error('contact_number')<small class="profile-field-error" id="contact_number-error" role="alert">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group profile-field">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->subDay()->format('Y-m-d') }}">
                        @error('date_of_birth')<small class="profile-field-error" id="date_of_birth-error" role="alert">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group profile-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" class="form-control profile-field-readonly" value="{{ $user->email }}" disabled readonly aria-describedby="email-help">
                        <small class="profile-help-text" id="email-help">Email address cannot be changed here.</small>
                    </div>
                </div>

                <div class="form-group profile-field profile-field--full">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="3" autocomplete="street-address">{{ old('address', $user->address) }}</textarea>
                    @error('address')<small class="profile-field-error" id="address-error" role="alert">{{ $message }}</small>@enderror
                </div>

                <div class="profile-actions">
                    <button type="submit" class="btn btn-primary" id="profile-save-btn">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        <span class="profile-btn-label">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Account Information --}}
    <section class="content-card profile-account-card" aria-labelledby="account-info-heading">
        <div class="card-header">
            <h2 class="card-title" id="account-info-heading"><i class="fas fa-building" aria-hidden="true"></i> Account Information</h2>
        </div>
        <div class="card-body">
            <div class="settings-summary-grid profile-account-grid">
                <div class="settings-summary-item">
                    <span class="settings-summary-item__label">Patient Type</span>
                    <span class="settings-summary-item__value">{{ $patientTypeLabel }}</span>
                </div>
                <div class="settings-summary-item">
                    <span class="settings-summary-item__label">Department</span>
                    <span class="settings-summary-item__value">{{ $user->department?->name ?? '—' }}</span>
                </div>
                <div class="settings-summary-item settings-summary-item--mono">
                    <span class="settings-summary-item__label">Student/Employee ID</span>
                    <span class="settings-summary-item__value">{{ $user->employee_student_id ?? '—' }}</span>
                </div>
                <div class="settings-summary-item">
                    <span class="settings-summary-item__label">Account Status</span>
                    <span class="settings-summary-item__value">
                        <span class="status-badge {{ $statusBadgeClass }}">
                            <i class="fas fa-circle" aria-hidden="true"></i>
                            {{ ucfirst($accountStatus) }}
                        </span>
                    </span>
                </div>
            </div>
            <p class="profile-help-text profile-account-note">These details are managed by the institution and cannot be edited here.</p>
        </div>
    </section>

    {{-- Security --}}
    <section class="content-card profile-security-card" aria-labelledby="security-heading">
        <div class="card-header">
            <h2 class="card-title" id="security-heading"><i class="fas fa-shield-halved" aria-hidden="true"></i> Security</h2>
        </div>
        <div class="card-body profile-security-body">
            <div class="profile-security-info">
                <div class="profile-security-info__icon" aria-hidden="true">
                    <i class="fas fa-lock"></i>
                </div>
                <div>
                    <h3 class="profile-security-info__title">Password</h3>
                    <p class="profile-security-info__desc">Your password protects access to your health services account.</p>
                    <p class="profile-security-info__meta">
                        <span class="profile-security-info__meta-label">Last updated</span>
                        @if($user->password_changed_at)
                            {{ $user->password_changed_at->format('F j, Y') }}
                        @else
                            Not changed since account creation
                        @endif
                    </p>
                </div>
            </div>
            <div class="profile-actions profile-actions--inline">
                <button type="button" class="btn btn-secondary" id="open-password-modal-btn" onclick="PortalProfile.openPasswordModal()">
                    <i class="fas fa-key" aria-hidden="true"></i> Change Password
                </button>
            </div>
        </div>
    </section>

</div>

{{-- Change Photo Modal --}}
<div id="photoModal" class="modal profile-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="photo-modal-title">
    <div class="modal-dialog profile-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="photo-modal-title">Change Profile Photo</h2>
                <button type="button" class="close" id="photo-modal-close" onclick="PortalProfile.closePhotoModal()" aria-label="Close">&times;</button>
            </div>
            <form method="post" action="{{ route('portal.profile.photo') }}" enctype="multipart/form-data" id="photo-form">
                @csrf
                <div class="modal-body">
                    <div class="profile-photo-preview-wrap">
                        <img id="photo-preview" src="{{ $user->profilePhotoUrl() ?? '' }}" alt="Profile photo preview" class="profile-photo-preview" @unless($user->hasProfilePhoto()) hidden @endunless>
                        <div id="photo-preview-placeholder" class="wpu-avatar wpu-avatar--profile profile-photo-preview-placeholder" @if($user->hasProfilePhoto()) hidden @endif aria-hidden="true">{{ $user->initials() }}</div>
                    </div>

                    <div class="form-group profile-field">
                        <label for="photo">Choose Image</label>
                        <div class="profile-file-upload">
                            <input type="file" name="photo" id="photo" class="profile-file-input" accept="image/jpeg,image/png,image/webp">
                            <label for="photo" class="btn btn-secondary btn-sm profile-file-label">
                                <i class="fas fa-image" aria-hidden="true"></i> Choose Image
                            </label>
                            <span class="profile-file-name" id="photo-file-name">No file selected</span>
                        </div>
                        <small class="profile-help-text">JPG, PNG or WebP &bull; Maximum 5 MB</small>
                        <small class="profile-field-error" id="photo-client-error" role="alert" hidden></small>
                        @error('photo')<small class="profile-field-error" id="photo-server-error" role="alert">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="PortalProfile.closePhotoModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="photo-upload-btn" disabled>
                        <i class="fas fa-upload" aria-hidden="true"></i>
                        <span class="profile-btn-label">Upload Photo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Change Password Modal --}}
<div id="passwordModal" class="modal profile-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="password-modal-title">
    <div class="modal-dialog profile-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="password-modal-title">Change Password</h2>
                <button type="button" class="close" id="password-modal-close" onclick="PortalProfile.closePasswordModal()" aria-label="Close">&times;</button>
            </div>
            <form method="post" action="{{ route('portal.profile.password') }}" id="password-form" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group profile-field">
                        <label for="current_password">Current Password</label>
                        <div class="profile-password-wrap">
                            <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                            <button type="button" class="profile-password-toggle" data-target="current_password" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye profile-password-toggle__show" aria-hidden="true"></i>
                                <i class="fas fa-eye-slash profile-password-toggle__hide" aria-hidden="true" hidden></i>
                            </button>
                        </div>
                        @error('current_password')<small class="profile-field-error" role="alert">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group profile-field">
                        <label for="password">New Password</label>
                        <div class="profile-password-wrap">
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password" minlength="8">
                            <button type="button" class="profile-password-toggle" data-target="password" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye profile-password-toggle__show" aria-hidden="true"></i>
                                <i class="fas fa-eye-slash profile-password-toggle__hide" aria-hidden="true" hidden></i>
                            </button>
                        </div>
                        <div class="profile-password-strength" id="password-strength" aria-live="polite">
                            <div class="profile-password-strength__bar" aria-hidden="true">
                                <span class="profile-password-strength__fill" id="password-strength-fill"></span>
                            </div>
                            <span class="profile-password-strength__label" id="password-strength-label"></span>
                        </div>
                        @error('password')<small class="profile-field-error" role="alert">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group profile-field">
                        <label for="password_confirmation">Confirm New Password</label>
                        <div class="profile-password-wrap">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password" minlength="8">
                            <button type="button" class="profile-password-toggle" data-target="password_confirmation" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye profile-password-toggle__show" aria-hidden="true"></i>
                                <i class="fas fa-eye-slash profile-password-toggle__hide" aria-hidden="true" hidden></i>
                            </button>
                        </div>
                        <small class="profile-password-match" id="password-match-msg" role="status" hidden></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="PortalProfile.closePasswordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="password-submit-btn">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        <span class="profile-btn-label">Update Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.profile-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Profile Header */
.profile-header-card .card-body {
    padding: 24px;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.profile-header__avatar-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex-shrink: 0;
}

.profile-header__avatar {
    position: relative;
    transition: transform .2s ease, box-shadow .2s ease;
    border-radius: 16px;
}

.profile-header__avatar:hover {
    transform: translateY(-2px);
}

.profile-header__avatar .wpu-avatar--profile,
.profile-header__avatar .wpu-avatar--photo {
    width: 120px !important;
    height: 120px !important;
    border-radius: 16px !important;
    font-size: 32px !important;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.18);
}

.profile-header__photo-btn {
    margin-top: 12px;
}

.profile-header__details {
    flex: 1;
    min-width: 200px;
}

.profile-header__name {
    margin: 0 0 6px;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--his-text, #0F172A);
    line-height: 1.3;
}

.profile-header__role,
.profile-header__email {
    margin: 0 0 4px;
    font-size: 14px;
    color: var(--his-text-muted, #64748B);
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-header__role i,
.profile-header__email i {
    width: 16px;
    text-align: center;
    color: var(--his-primary, #2563EB);
    opacity: 0.85;
}

/* Form Grid */
.profile-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0 16px;
}

.profile-field--full {
    grid-column: 1 / -1;
}

.profile-field-readonly {
    background: var(--his-bg-subtle, #F8FAFC) !important;
    color: var(--his-text-muted, #64748B) !important;
    cursor: not-allowed;
    border-style: dashed !important;
}

.profile-help-text {
    display: block;
    margin-top: 6px;
    color: var(--his-text-muted, #64748B);
    font-size: 12px;
    line-height: 1.45;
}

.profile-field-error {
    display: block;
    margin-top: 6px;
    color: var(--his-danger, #EF4444);
    font-size: 12px;
    line-height: 1.45;
}

.form-control.is-invalid {
    border-color: var(--his-danger, #EF4444) !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.profile-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 8px;
    padding-top: 8px;
}

.profile-actions--inline {
    justify-content: flex-start;
    margin-top: 0;
    padding-top: 0;
}

.profile-account-note {
    margin-top: 14px;
    margin-bottom: 0;
}

.profile-account-grid .status-badge {
    font-size: 11px;
}

/* Security Section */
.profile-security-body {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.profile-security-info {
    display: flex;
    gap: 16px;
    flex: 1;
    min-width: 240px;
}

.profile-security-info__icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #EFF6FF;
    color: #1D4ED8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

html[data-theme="dark"] .profile-security-info__icon {
    background: #0F1C33;
    color: #93C5FD;
}

.profile-security-info__title {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 700;
    color: var(--his-text, #0F172A);
}

.profile-security-info__desc {
    margin: 0 0 8px;
    font-size: 13px;
    color: var(--his-text-muted, #64748B);
    line-height: 1.5;
}

.profile-security-info__meta {
    margin: 0;
    font-size: 13px;
    color: var(--his-text, #0F172A);
}

.profile-security-info__meta-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--his-text-muted, #64748B);
    margin-bottom: 2px;
}

/* Photo Modal */
.profile-modal-dialog {
    max-width: 480px;
}

.profile-photo-preview-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 16px;
}

.profile-photo-preview {
    width: 120px;
    height: 120px;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
}

.profile-photo-preview-placeholder {
    width: 120px !important;
    height: 120px !important;
    border-radius: 16px !important;
    font-size: 32px !important;
}

.profile-file-upload {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.profile-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.profile-file-name {
    font-size: 13px;
    color: var(--his-text-muted, #64748B);
    word-break: break-all;
}

/* Password Modal */
.profile-password-wrap {
    position: relative;
}

.profile-password-wrap .form-control {
    padding-right: 44px !important;
}

.profile-password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--his-text-muted, #64748B);
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    line-height: 1;
    transition: color .15s, background .15s;
}

.profile-password-toggle:hover {
    color: var(--his-primary, #2563EB);
    background: rgba(37, 99, 235, 0.08);
}

.profile-password-toggle:focus-visible {
    outline: 2px solid var(--his-primary, #2563EB);
    outline-offset: 2px;
}

.profile-password-strength {
    margin-top: 8px;
}

.profile-password-strength__bar {
    height: 4px;
    background: var(--his-border, #E2E8F0);
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 4px;
}

.profile-password-strength__fill {
    display: block;
    height: 100%;
    width: 0;
    border-radius: 999px;
    transition: width .25s ease, background .25s ease;
}

.profile-password-strength__label {
    font-size: 12px;
    color: var(--his-text-muted, #64748B);
}

.profile-password-strength.is-weak .profile-password-strength__fill { width: 25%; background: #EF4444; }
.profile-password-strength.is-fair .profile-password-strength__fill { width: 50%; background: #F59E0B; }
.profile-password-strength.is-good .profile-password-strength__fill { width: 75%; background: #3B82F6; }
.profile-password-strength.is-strong .profile-password-strength__fill { width: 100%; background: #22C55E; }

.profile-password-strength.is-weak .profile-password-strength__label { color: #EF4444; }
.profile-password-strength.is-fair .profile-password-strength__label { color: #D97706; }
.profile-password-strength.is-good .profile-password-strength__label { color: #2563EB; }
.profile-password-strength.is-strong .profile-password-strength__label { color: #16A34A; }

.profile-password-match {
    display: block;
    margin-top: 6px;
    font-size: 12px;
}

.profile-password-match.is-match {
    color: #16A34A;
}

.profile-password-match.is-mismatch {
    color: #EF4444;
}

/* Modals */
#photoModal.show,
#passwordModal.show {
    display: flex;
}

.profile-modal .modal-body {
    padding: 20px;
}

/* Loading state */
.btn.is-loading {
    pointer-events: none;
    opacity: 0.75;
}

.btn.is-loading .profile-btn-label::after {
    content: '…';
}

/* Avatar component overrides */
.wpu-avatar--profile { width: 120px !important; height: 120px !important; border-radius: 16px !important; font-size: 32px !important; }
.wpu-avatar--topbar { width: 28px !important; height: 28px !important; border-radius: 8px !important; font-size: 11px !important; min-width: 28px; }
.user-avatar.wpu-avatar--photo, .wpu-avatar--photo { object-fit: cover; padding: 0 !important; display: inline-flex !important; }
.topbar-profile-btn .av { overflow: hidden; padding: 0; background: none !important; box-shadow: none !important; }
.topbar-profile-btn .av.wpu-avatar--photo, .topbar-profile-btn img.wpu-avatar--topbar { background: transparent !important; }

/* Responsive */
@media (max-width: 640px) {
    .profile-form-grid {
        grid-template-columns: 1fr;
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
    }

    .profile-header__role,
    .profile-header__email {
        justify-content: center;
    }

    .profile-header__photo-btn {
        width: 100%;
    }

    .profile-actions,
    .profile-actions--inline {
        justify-content: stretch;
    }

    .profile-actions .btn,
    .profile-actions--inline .btn {
        width: 100%;
    }

    .profile-security-body {
        flex-direction: column;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    var PHOTO_MAX_BYTES = 5 * 1024 * 1024;
    var PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    var lastFocusedElement = null;

    function qs(id) { return document.getElementById(id); }

    function setBtnLoading(btn, loading, loadingLabel) {
        if (!btn) return;
        var label = btn.querySelector('.profile-btn-label');
        if (loading) {
            btn.dataset.originalLabel = label ? label.textContent : '';
            btn.classList.add('is-loading');
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            if (label && loadingLabel) label.textContent = loadingLabel;
        } else {
            btn.classList.remove('is-loading');
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            if (label && btn.dataset.originalLabel) label.textContent = btn.dataset.originalLabel;
        }
    }

    function openModal(modalId, triggerEl, focusId) {
        var modal = qs(modalId);
        if (!modal) return;
        lastFocusedElement = triggerEl || document.activeElement;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var focusEl = focusId ? qs(focusId) : modal.querySelector('input, button, select, textarea');
        if (focusEl) setTimeout(function () { focusEl.focus(); }, 50);
    }

    function closeModal(modalId) {
        var modal = qs(modalId);
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    function initModalBackdrop(modalId, closeFn) {
        var modal = qs(modalId);
        if (!modal) return;
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeFn();
        });
    }

    function initModalEscape(modalId, closeFn) {
        document.addEventListener('keydown', function (e) {
            var modal = qs(modalId);
            if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
                e.preventDefault();
                closeFn();
            }
        });
    }

    /* ---------- Photo validation & preview ---------- */
    function validatePhotoFile(file) {
        var errorEl = qs('photo-client-error');
        var uploadBtn = qs('photo-upload-btn');

        if (!file) {
            if (errorEl) { errorEl.hidden = true; errorEl.textContent = ''; }
            if (uploadBtn) uploadBtn.disabled = true;
            return false;
        }

        if (PHOTO_TYPES.indexOf(file.type) === -1) {
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.textContent = 'Please select a JPG, PNG, or WebP image.';
            }
            if (uploadBtn) uploadBtn.disabled = true;
            return false;
        }

        if (file.size > PHOTO_MAX_BYTES) {
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.textContent = 'The selected image is too large. Maximum file size is 5 MB.';
            }
            if (uploadBtn) uploadBtn.disabled = true;
            return false;
        }

        if (errorEl) { errorEl.hidden = true; errorEl.textContent = ''; }
        if (uploadBtn) uploadBtn.disabled = false;
        return true;
    }

    function initPhotoModal() {
        var photoInput = qs('photo');
        var fileNameEl = qs('photo-file-name');
        var photoForm = qs('photo-form');

        if (photoInput) {
            photoInput.addEventListener('change', function () {
                var file = this.files && this.files[0];
                if (fileNameEl) fileNameEl.textContent = file ? file.name : 'No file selected';

                if (!validatePhotoFile(file)) return;

                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = qs('photo-preview');
                    var placeholder = qs('photo-preview-placeholder');
                    if (img) {
                        img.src = e.target.result;
                        img.hidden = false;
                    }
                    if (placeholder) placeholder.hidden = true;
                };
                reader.readAsDataURL(file);
            });
        }

        if (photoForm) {
            var submitting = false;
            photoForm.addEventListener('submit', function (e) {
                var file = photoInput && photoInput.files && photoInput.files[0];
                if (!validatePhotoFile(file)) {
                    e.preventDefault();
                    return;
                }
                if (submitting) {
                    e.preventDefault();
                    return;
                }
                submitting = true;
                setBtnLoading(qs('photo-upload-btn'), true, 'Uploading');
            });
        }
    }

    /* ---------- Password strength & match ---------- */
    function scorePassword(value) {
        if (!value) return { level: '', label: '' };
        if (value.length < 8) return { level: 'weak', label: 'Too short' };

        var score = 0;
        if (value.length >= 10) score++;
        if (value.length >= 14) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^a-zA-Z0-9]/.test(value)) score++;

        if (score <= 1) return { level: 'weak', label: 'Weak' };
        if (score === 2) return { level: 'fair', label: 'Fair' };
        if (score === 3) return { level: 'good', label: 'Good' };
        return { level: 'strong', label: 'Strong' };
    }

    function updatePasswordStrength() {
        var input = qs('password');
        var container = qs('password-strength');
        var label = qs('password-strength-label');
        if (!input || !container || !label) return;

        var result = scorePassword(input.value);
        container.className = 'profile-password-strength' + (result.level ? ' is-' + result.level : '');
        label.textContent = result.label;
    }

    function updatePasswordMatch() {
        var password = qs('password');
        var confirm = qs('password_confirmation');
        var msg = qs('password-match-msg');
        if (!password || !confirm || !msg) return;

        if (!confirm.value) {
            msg.hidden = true;
            msg.textContent = '';
            msg.className = 'profile-password-match';
            return;
        }

        msg.hidden = false;
        if (password.value === confirm.value) {
            msg.textContent = '\u2713 Passwords match';
            msg.className = 'profile-password-match is-match';
        } else {
            msg.textContent = '\u26A0 Passwords do not match';
            msg.className = 'profile-password-match is-mismatch';
        }
    }

    function initPasswordToggles() {
        document.querySelectorAll('.profile-password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var input = qs(targetId);
                if (!input) return;

                var isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');

                var showIcon = btn.querySelector('.profile-password-toggle__show');
                var hideIcon = btn.querySelector('.profile-password-toggle__hide');
                if (showIcon) showIcon.hidden = isHidden;
                if (hideIcon) hideIcon.hidden = !isHidden;
            });
        });
    }

    function initPasswordForm() {
        var passwordInput = qs('password');
        var confirmInput = qs('password_confirmation');
        var passwordForm = qs('password-form');

        if (passwordInput) {
            passwordInput.addEventListener('input', function () {
                updatePasswordStrength();
                updatePasswordMatch();
            });
        }
        if (confirmInput) {
            confirmInput.addEventListener('input', updatePasswordMatch);
        }

        if (passwordForm) {
            var submitting = false;
            passwordForm.addEventListener('submit', function (e) {
                if (submitting) {
                    e.preventDefault();
                    return;
                }
                submitting = true;
                setBtnLoading(qs('password-submit-btn'), true, 'Updating');
            });
        }
    }

    /* ---------- Profile form: loading & unsaved changes ---------- */
    function initProfileForm() {
        var form = qs('profile-form');
        if (!form) return;

        var fields = ['name', 'contact_number', 'date_of_birth', 'address'];
        var initial = {};
        fields.forEach(function (id) {
            var el = qs(id);
            if (el) initial[id] = el.value;
        });

        function hasUnsavedChanges() {
            return fields.some(function (id) {
                var el = qs(id);
                return el && el.value !== initial[id];
            });
        }

        function warnUnsavedChanges(e) {
            if (hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = '';
            }
        }

        window.addEventListener('beforeunload', warnUnsavedChanges);

        var submitting = false;
        form.addEventListener('submit', function () {
            window.removeEventListener('beforeunload', warnUnsavedChanges);
            if (submitting) return;
            submitting = true;
            setBtnLoading(qs('profile-save-btn'), true, 'Saving');
        });
    }

    /* ---------- Public API ---------- */
    window.PortalProfile = {
        openPhotoModal: function () {
            openModal('photoModal', qs('open-photo-modal-btn'), 'photo');
        },
        closePhotoModal: function () {
            closeModal('photoModal');
        },
        openPasswordModal: function () {
            openModal('passwordModal', qs('open-password-modal-btn'), 'current_password');
        },
        closePasswordModal: function () {
            closeModal('passwordModal');
        }
    };

    /* ---------- Init ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        initPhotoModal();
        initPasswordToggles();
        initPasswordForm();
        initProfileForm();
        initModalBackdrop('photoModal', PortalProfile.closePhotoModal);
        initModalBackdrop('passwordModal', PortalProfile.closePasswordModal);
        initModalEscape('photoModal', PortalProfile.closePhotoModal);
        initModalEscape('passwordModal', PortalProfile.closePasswordModal);

        @if($errors->has('photo'))
        PortalProfile.openPhotoModal();
        @endif
        @if($errors->has('current_password') || $errors->has('password'))
        PortalProfile.openPasswordModal();
        @endif
    });
})();
</script>
@endpush
