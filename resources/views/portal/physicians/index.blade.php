@extends(auth('portal')->check() ? 'layouts.portal-app' : 'layouts.portal-public')
@section('title', 'Physicians')
@section('page-heading', 'Physicians')
@section('page-description', 'Browse available physicians and book a consultation.')
@section('content')
@guest('portal')
<div class="portal-page-intro">
    <h2 class="portal-page-title">Physicians</h2>
    <p class="portal-page-desc">Browse available physicians and book a consultation.</p>
</div>
@endguest

<div class="portal-stat-grid portal-stat-grid--count">
    <div class="portal-stat">
        <div class="portal-stat__label">Available physicians</div>
        <div class="portal-stat__value">{{ $physicians->count() }}</div>
    </div>
</div>

<div class="panels-grid portal-physician-grid">
@forelse($physicians as $p)
    <article class="panel-card portal-physician-card">
        <div class="panel-header">
            <div class="panel-icon" aria-hidden="true"><i class="fas fa-user-md"></i></div>
            <div class="portal-physician-card__head">
                <div class="panel-title">{{ $p->displayName() }}</div>
                @if($p->specialization)
                    <p class="portal-physician-meta">{{ $p->specialization }}</p>
                @endif
            </div>
        </div>
        <div class="panel-body">
            @if($p->consultation_location)
                <p class="portal-physician-detail"><i class="fas fa-map-marker-alt"></i><span>{{ $p->consultation_location }}</span></p>
            @endif
            @if($p->consultation_duration)
                <p class="portal-physician-detail"><i class="fas fa-clock"></i><span>{{ $p->consultation_duration }} min per visit</span></p>
            @endif
            @auth('portal')
                <a href="{{ route('portal.book') }}?physician_id={{ $p->id }}" class="btn btn-primary btn-sm"><i class="fas fa-calendar-plus"></i> Book Consultation</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Login to Book</a>
            @endauth
        </div>
    </article>
@empty
    <div class="panel-card portal-physician-card portal-physician-card--empty">
        <div class="panel-body">
            <p class="table-empty__title" style="margin:0">No physicians available</p>
            <p class="portal-page-desc" style="margin:8px 0 0">Please check back later or contact the clinic for assistance.</p>
        </div>
    </div>
@endforelse
</div>

@guest('portal')
<p class="portal-page-actions"><a href="{{ route('portal.home') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
@endguest
@endsection
