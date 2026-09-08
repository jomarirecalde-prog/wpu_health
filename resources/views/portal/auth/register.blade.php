@extends('layouts.portal-public')
@section('title', 'Register')
@section('content')
<div class="portal-auth-wrap" style="max-width:520px">
    <div class="portal-card"><div class="portal-card__head"><i class="fas fa-user-plus"></i> Create Account</div>
        <div class="portal-card__body">
            <form method="post" action="{{ route('portal.register') }}">@csrf
                <div class="form-group"><label>Full Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                <div class="form-group"><label>Contact Number</label><input name="contact_number" class="form-control" value="{{ old('contact_number') }}" required></div>
                <div class="form-group"><label>Patient Type</label><select name="patient_type_id" class="form-control"><option value="">— Select —</option>@foreach($patientTypes as $t)<option value="{{ $t->id }}" @selected(old('patient_type_id')==$t->id)>{{ $t->type_name }}</option>@endforeach</select></div>
                <div class="form-group"><label>Department</label><select name="department_id" class="form-control"><option value="">— Select —</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id')==$d->id)>{{ $d->name }}</option>@endforeach</select></div>
                <div class="form-group"><label>Student/Employee ID</label><input name="employee_student_id" class="form-control" value="{{ old('employee_student_id') }}"></div>
                <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}"></div>
                <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea></div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-user-plus"></i> Register</button>
            </form>
        </div>
    </div>
</div>
@endsection
