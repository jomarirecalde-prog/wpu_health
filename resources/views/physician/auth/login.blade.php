@extends('layouts.portal-public')
@section('title', 'Physician Login')
@section('content')
<div class="portal-auth-wrap">
    <div class="portal-card"><div class="portal-card__head"><i class="fas fa-stethoscope"></i> Physician Login</div>
        <div class="portal-card__body">
            <form method="post" action="{{ route('physician.login') }}">@csrf
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
        </div>
    </div>
</div>
@endsection
