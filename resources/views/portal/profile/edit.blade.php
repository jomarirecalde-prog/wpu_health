@extends('layouts.portal-app')
@section('title', 'Profile')
@section('page-heading', 'My Profile')
@section('content')
<div class="portal-card"><div class="portal-card__body">
    <form method="post" action="{{ route('portal.profile.update') }}">@csrf @method('PUT')
        <div class="form-group"><label>Full Name</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
        <div class="form-group"><label>Email</label><input type="email" class="form-control" value="{{ $user->email }}" disabled></div>
        <div class="form-group"><label>Contact Number</label><input name="contact_number" class="form-control" value="{{ old('contact_number', $user->contact_number) }}" required></div>
        <div class="form-group"><label>Patient Type</label><select name="patient_type_id" class="form-control"><option value="">—</option>@foreach($patientTypes as $t)<option value="{{ $t->id }}" @selected(old('patient_type_id',$user->patient_type_id)==$t->id)>{{ $t->type_name }}</option>@endforeach</select></div>
        <div class="form-group"><label>Department</label><select name="department_id" class="form-control"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id',$user->department_id)==$d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="form-group"><label>Student/Employee ID</label><input name="employee_student_id" class="form-control" value="{{ old('employee_student_id', $user->employee_student_id) }}"></div>
        <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"></div>
        <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="2">{{ old('address', $user->address) }}</textarea></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
    </form>
</div></div>
@endsection
