@extends('layouts.his-admin')
@section('title', 'Edit Patient')
@section('page-heading', 'Edit Patient')
@section('page-description', 'Update this patient account. The current password is never displayed.')
@php $activeSection = 'portal-users'; @endphp

@section('content')
<form method="post" action="{{ route('admin.calendar.portal-users.update', $user) }}" enctype="multipart/form-data" class="portal-user-edit" id="portal-user-form" novalidate>
    @csrf
    @method('PUT')

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-id-card"></i> Personal Information</h2>
        </div>
        <div class="card-body">
            <div class="settings-form-grid-2">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                    <small class="field-error" id="name-error" role="alert" @unless($errors->has('name')) hidden @endunless>{{ $errors->first('name') }}</small>
                </div>
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $user->contact_number) }}" maxlength="50" autocomplete="tel">
                    <small class="field-error" id="contact_number-error" role="alert" @unless($errors->has('contact_number')) hidden @endunless>{{ $errors->first('contact_number') }}</small>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->subDay()->format('Y-m-d') }}">
                    <small class="field-error" id="date_of_birth-error" role="alert" @unless($errors->has('date_of_birth')) hidden @endunless>{{ $errors->first('date_of_birth') }}</small>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="3" maxlength="500" autocomplete="street-address">{{ old('address', $user->address) }}</textarea>
                    <small class="field-error" id="address-error" role="alert" @unless($errors->has('address')) hidden @endunless>{{ $errors->first('address') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-building"></i> Account Information</h2>
        </div>
        <div class="card-body">
            <div class="settings-form-grid-2">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                    <small class="field-error" id="email-error" role="alert" @unless($errors->has('email')) hidden @endunless>{{ $errors->first('email') }}</small>
                </div>
                <div class="form-group">
                    <label for="patient_type_id">Patient Type</label>
                    <select name="patient_type_id" id="patient_type_id" class="form-control @error('patient_type_id') is-invalid @enderror">
                        <option value="">— Select —</option>
                        @foreach($patientTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('patient_type_id', $user->patient_type_id) === (string) $type->id)>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" id="patient_type_id-error" role="alert" @unless($errors->has('patient_type_id')) hidden @endunless>{{ $errors->first('patient_type_id') }}</small>
                </div>
                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select name="department_id" id="department_id" class="form-control @error('department_id') is-invalid @enderror">
                        <option value="">— Select —</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" id="department_id-error" role="alert" @unless($errors->has('department_id')) hidden @endunless>{{ $errors->first('department_id') }}</small>
                </div>
                <div class="form-group">
                    <label for="employee_student_id">Student/Employee ID</label>
                    <input type="text" name="employee_student_id" id="employee_student_id" class="form-control @error('employee_student_id') is-invalid @enderror" value="{{ old('employee_student_id', $user->employee_student_id) }}" maxlength="100">
                    <small class="field-error" id="employee_student_id-error" role="alert" @unless($errors->has('employee_student_id')) hidden @endunless>{{ $errors->first('employee_student_id') }}</small>
                </div>
                <div class="form-group">
                    <label for="status">Account Status</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $user->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <small class="field-error" id="status-error" role="alert" @unless($errors->has('status')) hidden @endunless>{{ $errors->first('status') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-camera"></i> Profile Photo</h2>
        </div>
        <div class="card-body">
            <div class="portal-user-photo-editor">
                <div class="portal-user-photo-preview-wrap">
                    <img id="photo-preview" src="{{ $user->profilePhotoUrl() ?? '' }}" alt="Profile photo preview" class="portal-user-photo-preview" @unless($user->hasProfilePhoto()) hidden @endunless>
                    <div id="photo-preview-placeholder" class="wpu-avatar wpu-avatar--profile portal-user-photo-placeholder" @if($user->hasProfilePhoto()) hidden @endif aria-hidden="true">{{ $user->initials() }}</div>
                </div>
                <div class="form-group" style="margin:0">
                    <input type="file" name="photo" id="photo" class="portal-file-input" accept="image/jpeg,image/png,image/webp">
                    <label for="photo" class="btn btn-secondary btn-sm">
                        <i class="fas fa-image" aria-hidden="true"></i> Change Photo
                    </label>
                    <span class="form-helper" id="photo-file-name">JPG, PNG or WebP &bull; Maximum 5 MB</span>
                    <small class="field-error" id="photo-error" role="alert" @unless($errors->has('photo')) hidden @endunless>{{ $errors->first('photo') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-body">
            <div class="settings-form-actions" style="justify-content:flex-end">
                <a href="{{ route('admin.calendar.portal-users.show', $user) }}" class="btn btn-secondary" id="portal-user-cancel">Cancel</a>
                <button type="submit" class="btn btn-primary" id="portal-user-save">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    <span class="btn-label">Save Changes</span>
                </button>
            </div>
        </div>
    </div>
</form>

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-shield-halved"></i> Security</h2>
    </div>
    <div class="card-body">
        <p class="form-helper" style="margin-top:0">The current password cannot be viewed or recovered. Set a new password only when required.</p>
        <p class="form-helper">
            Last updated:
            @if($user->password_changed_at)
                {{ $user->password_changed_at->format('F j, Y') }}
            @else
                Not changed since account creation
            @endif
        </p>

        <form method="post" action="{{ route('admin.calendar.portal-users.password', $user) }}" id="portal-password-form" novalidate>
            @csrf
            @method('PUT')
            <div class="settings-form-grid-2">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="portal-password-row">
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8">
                        <button type="button" class="btn btn-secondary btn-sm" id="generate-password-btn">Generate</button>
                    </div>
                    <small class="form-helper">Minimum 8 characters.</small>
                    <small class="field-error" id="password-error" role="alert" @unless($errors->has('password')) hidden @endunless>{{ $errors->first('password') }}</small>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm New Password</label>
                    <div class="portal-password-row">
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password" minlength="8">
                        <button type="button" class="btn btn-secondary btn-sm" id="copy-password-btn" hidden>Copy</button>
                    </div>
                    <small class="field-error" id="password_confirmation-error" role="alert" hidden></small>
                </div>
            </div>
            <div class="settings-form-actions">
                <button type="submit" class="btn btn-primary" id="password-save-btn">
                    <i class="fas fa-key" aria-hidden="true"></i>
                    <span class="btn-label">Set New Password</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.portal-user-edit .content-card { margin-bottom: 18px; }
.portal-user-photo-editor { display:flex; flex-direction:column; align-items:flex-start; gap:14px; }
.portal-user-photo-preview-wrap { display:flex; }
.portal-user-photo-preview, .portal-user-photo-placeholder {
    width:120px; height:120px; border-radius:16px; object-fit:cover;
}
.portal-user-photo-placeholder {
    display:flex; align-items:center; justify-content:center;
    background: var(--secondary-blue, #2563eb); color:#fff; font-weight:700; font-size:28px;
}
.portal-file-input { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }
.portal-password-row { display:flex; gap:8px; align-items:stretch; }
.portal-password-row .form-control { flex:1; min-width:0; }
.field-error { display:block; margin-top:6px; color:#ef4444; font-size:12px; }
.form-control.is-invalid { border-color:#ef4444; }
@media (max-width: 640px) {
    .portal-password-row { flex-direction:column; }
    .settings-form-actions { width:100%; }
    .settings-form-actions .btn { flex:1; justify-content:center; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('portal-user-form');
    var passwordForm = document.getElementById('portal-password-form');
    var saveBtn = document.getElementById('portal-user-save');
    var cancelBtn = document.getElementById('portal-user-cancel');
    var photoInput = document.getElementById('photo');
    var preview = document.getElementById('photo-preview');
    var placeholder = document.getElementById('photo-preview-placeholder');
    var photoError = document.getElementById('photo-error');
    var photoName = document.getElementById('photo-file-name');
    var dirty = false;
    var submitting = false;
    var generatedPlain = '';

    function showError(id, message) {
        var el = document.getElementById(id);
        var field = document.getElementById(id.replace(/-error$/, ''));
        if (el) {
            el.textContent = message || '';
            el.hidden = !message;
        }
        if (field) field.classList.toggle('is-invalid', !!message);
    }

    function snapshot(formEl) {
        return new URLSearchParams(new FormData(formEl)).toString();
    }

    var initial = form ? snapshot(form) : '';

    if (form) {
        form.addEventListener('input', function () { dirty = snapshot(form) !== initial; });
        form.addEventListener('change', function () { dirty = snapshot(form) !== initial; });
    }

    window.addEventListener('beforeunload', function (e) {
        if (dirty && !submitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function (e) {
            if (!dirty) return;
            e.preventDefault();
            var href = cancelBtn.getAttribute('href');
            var confirmFn = window.confirmAction;
            var proceed = function () { dirty = false; window.location.href = href; };
            if (confirmFn) {
                confirmFn('Unsaved Changes', 'You have changes that have not been saved.', 'Leave', 'Stay', 'warning').then(function (ok) {
                    if (ok) proceed();
                });
            } else if (window.confirm('You have changes that have not been saved.')) {
                proceed();
            }
        });
    }

    if (photoInput) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files && photoInput.files[0];
            showError('photo-error', '');
            if (!file) return;
            var allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (allowed.indexOf(file.type) === -1) {
                showError('photo-error', 'Invalid file type. Allowed formats: JPG, PNG, WebP.');
                photoInput.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showError('photo-error', 'File too large. Maximum size is 5 MB.');
                photoInput.value = '';
                return;
            }
            if (photoName) photoName.textContent = file.name;
            var reader = new FileReader();
            reader.onload = function (ev) {
                if (preview) {
                    preview.src = ev.target.result;
                    preview.hidden = false;
                }
                if (placeholder) placeholder.hidden = true;
            };
            reader.readAsDataURL(file);
            dirty = true;
        });
    }

    if (form && saveBtn) {
        form.addEventListener('submit', function (e) {
            showError('name-error', form.name.value.trim() ? '' : 'Full name is required.');
            var email = form.email.value.trim();
            var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            showError('email-error', email ? (emailOk ? '' : 'Please enter a valid email address.') : 'Email is required.');
            if (!form.name.value.trim() || !emailOk) {
                e.preventDefault();
                return;
            }
            if (submitting) {
                e.preventDefault();
                return;
            }
            submitting = true;
            dirty = false;
            saveBtn.disabled = true;
            var label = saveBtn.querySelector('.btn-label');
            if (label) label.textContent = 'Saving...';
        });
    }

    function generatePassword() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        var out = '';
        for (var i = 0; i < bytes.length; i++) out += chars[bytes[i] % chars.length];
        return out;
    }

    var generateBtn = document.getElementById('generate-password-btn');
    var copyBtn = document.getElementById('copy-password-btn');
    var passwordInput = document.getElementById('password');
    var confirmInput = document.getElementById('password_confirmation');
    var passwordSaveBtn = document.getElementById('password-save-btn');

    if (generateBtn && passwordInput && confirmInput) {
        generateBtn.addEventListener('click', function () {
            generatedPlain = generatePassword();
            passwordInput.value = generatedPlain;
            confirmInput.value = generatedPlain;
            passwordInput.type = 'text';
            confirmInput.type = 'text';
            if (copyBtn) copyBtn.hidden = false;
            showError('password-error', '');
            showError('password_confirmation-error', '');
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            if (!generatedPlain) return;
            navigator.clipboard.writeText(generatedPlain).then(function () {
                copyBtn.textContent = 'Copied';
                copyBtn.disabled = true;
                generatedPlain = '';
                if (passwordInput) passwordInput.type = 'password';
                if (confirmInput) confirmInput.type = 'password';
            });
        });
    }

    if (passwordForm && passwordSaveBtn) {
        passwordForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var pw = passwordInput.value;
            var cf = confirmInput.value;
            showError('password-error', '');
            showError('password_confirmation-error', '');
            if (!pw || pw.length < 8) {
                showError('password-error', 'Password must be at least 8 characters.');
                return;
            }
            if (pw !== cf) {
                showError('password_confirmation-error', 'Password confirmation does not match.');
                return;
            }
            var patientName = @json($user->name);
            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, function (ch) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
                });
            }
            var message = 'You are about to change the password for <strong>' + escapeHtml(patientName) + '</strong>. The patient\'s current password cannot be recovered.';
            var confirmFn = window.confirmAction;
            var submitNow = function () {
                passwordSaveBtn.disabled = true;
                var label = passwordSaveBtn.querySelector('.btn-label');
                if (label) label.textContent = 'Saving...';
                generatedPlain = '';
                passwordForm.submit();
            };
            if (confirmFn) {
                confirmFn('Change Password?', message, 'Change Password', 'Cancel', 'warning').then(function (ok) {
                    if (ok) submitNow();
                });
            } else if (window.confirm('Change Password?\n\nYou are about to change the password for:\n\n' + patientName + '\n\nThe patient\'s current password cannot be recovered.\n\nContinue?')) {
                submitNow();
            }
        });
    }
})();
</script>
@endpush
