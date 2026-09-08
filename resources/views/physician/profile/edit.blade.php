@extends('layouts.physician-app')
@section('title', 'Profile')
@section('page-heading', 'My Profile')
@section('content')
<div class="portal-card"><div class="portal-card__body">
    <form method="post" action="{{ route('physician.profile.update') }}">@csrf @method('PUT')
        <div class="form-group"><label>Name</label><input name="name" class="form-control" value="{{ $physician->name }}" required></div>
        <div class="form-group"><label>Professional Title</label><input name="professional_title" class="form-control" value="{{ $physician->professional_title }}"></div>
        <div class="form-group"><label>Specialization</label><input name="specialization" class="form-control" value="{{ $physician->specialization }}"></div>
        <div class="form-group"><label>Location</label><input name="consultation_location" class="form-control" value="{{ $physician->consultation_location }}"></div>
        <div class="form-group"><label>New Password</label><input type="password" name="password" class="form-control"></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" name="password_confirmation" class="form-control"></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    </form>
</div></div>
@endsection
