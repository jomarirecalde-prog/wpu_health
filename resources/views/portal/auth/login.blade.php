@extends('layouts.portal-public')
@section('title', 'Login')
@section('content')
<div class="portal-auth-wrap">
    <div class="portal-card"><div class="portal-card__head"><i class="fas fa-sign-in-alt"></i> Patient Login</div>
        <div class="portal-card__body">
            <form method="post" action="{{ route('portal.login') }}">@csrf
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="form-group"><label class="settings-checkbox-row"><input type="checkbox" name="remember" style="width:auto"> Remember me</label></div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            <p style="margin-top:12px;text-align:center;font-size:12px">No account? <a href="{{ route('portal.register') }}">Register</a> · <a href="{{ route('portal.home') }}">Home</a></p>
        </div>
    </div>
</div>
@endsection
