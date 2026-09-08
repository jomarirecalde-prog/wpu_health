@extends('layouts.physician-app')
@section('title', 'Profile')
@section('page-heading', 'My Profile')
@section('page-description', 'Manage your professional profile and account settings.')
@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user"></i> Profile Information</h2>
    </div>
    <div class="card-body">
        <form method="post" action="{{ route('physician.profile.update') }}">@csrf @method('PUT')
            <div class="form-group"><label for="name">Name</label><input name="name" id="name" class="form-control" value="{{ $physician->name }}" required></div>
            <div class="form-group"><label for="professional_title">Professional Title</label><input name="professional_title" id="professional_title" class="form-control" value="{{ $physician->professional_title }}"></div>
            <div class="form-group"><label for="specialization">Specialization</label><input name="specialization" id="specialization" class="form-control" value="{{ $physician->specialization }}"></div>
            <div class="form-group"><label for="consultation_location">Location</label><input name="consultation_location" id="consultation_location" class="form-control" value="{{ $physician->consultation_location }}"></div>
            <div class="form-group"><label for="password">New Password</label><input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current password"></div>
            <div class="form-group"><label for="password_confirmation">Confirm Password</label><input type="password" name="password_confirmation" id="password_confirmation" class="form-control"></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
        </form>
    </div>
</div>
@endsection
