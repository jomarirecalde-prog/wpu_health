@extends('layouts.portal-public')
@section('title', 'Physicians')
@section('content')
<h2 style="color:var(--primary);margin-bottom:16px;font-size:18px">Available Physicians</h2>
<div class="panels-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
@forelse($physicians as $p)
<div class="panel-card"><div class="panel-header"><div class="panel-icon"><i class="fas fa-user-md"></i></div><div class="panel-title">{{ $p->displayName() }}</div></div>
<div class="panel-body">@if($p->specialization)<p style="color:var(--gray);margin-bottom:8px">{{ $p->specialization }}</p>@endif @if($p->consultation_location)<p style="font-size:12px"><i class="fas fa-map-marker-alt"></i> {{ $p->consultation_location }}</p>@endif</div></div>
@empty<p>No physicians available.</p>@endforelse
</div>
<p style="margin-top:16px"><a href="{{ route('portal.home') }}">← Back to Home</a></p>
@endsection
