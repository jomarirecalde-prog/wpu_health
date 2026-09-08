@extends(auth('portal')->check() ? 'layouts.portal-app' : 'layouts.portal-public')
@section('title', 'Physicians')
@section('page-heading', 'Physicians')
@section('page-description', 'Browse available physicians and book a consultation.')
@section('content')
<div class="stats-grid dashboard-stats" style="margin-bottom:18px">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $physicians->count() }}</div>
                <div class="stat-label">Available Physicians</div>
            </div>
            <div class="stat-icon blue"><i class="fas fa-user-md"></i></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px">
@forelse($physicians as $p)
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user-md"></i> {{ $p->displayName() }}</h2>
    </div>
    <div class="card-body">
        @if($p->specialization)<p style="color:var(--his-text-muted,#64748B);margin-bottom:8px"><strong>Specialization:</strong> {{ $p->specialization }}</p>@endif
        @if($p->professional_title)<p style="font-size:13px;margin-bottom:8px">{{ $p->professional_title }}</p>@endif
        @if($p->consultation_location)<p style="font-size:13px;margin-bottom:12px"><i class="fas fa-map-marker-alt"></i> {{ $p->consultation_location }}</p>@endif
        <p style="font-size:13px;color:var(--his-text-muted,#64748B);margin-bottom:12px">WPU Health Services</p>
        @auth('portal')
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('portal.book') }}?physician_id={{ $p->id }}" class="btn btn-primary btn-sm"><i class="fas fa-calendar-plus"></i> Book Consultation</a>
        </div>
        @else
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Login to Book</a>
        @endauth
    </div>
</div>
@empty
<div class="content-card"><div class="card-body"><div class="table-empty"><p class="table-empty__title">No physicians available</p></div></div></div>
@endforelse
</div>

@guest('portal')
<p style="margin-top:16px"><a href="{{ route('portal.home') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
@endguest
@endsection
