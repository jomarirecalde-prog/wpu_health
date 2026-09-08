@extends('layouts.portal-app')
@section('title', 'Notifications')
@section('page-heading', 'Notifications')
@section('page-description', 'Updates about your consultation appointments.')
@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-bell"></i> Notifications</h2>
    </div>
    <div class="card-body">
        @forelse($notifications as $n)
        <div class="recent-row" style="padding:12px 0;border-bottom:1px solid var(--his-border,#E2E8F0);display:flex;justify-content:space-between;align-items:flex-start;gap:12px;{{ $n->read_at ? 'opacity:.75' : '' }}">
            <div class="meta">
                <strong style="display:block;margin-bottom:4px">{{ $n->title }}</strong>
                <span style="font-size:13px;color:var(--his-text-muted,#64748B)">{{ $n->message }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                <span class="status-pill">{{ $n->created_at->diffForHumans() }}</span>
                @unless($n->read_at)
                <button type="button" class="btn btn-secondary btn-sm" onclick="markRead({{ $n->id }}, this)"><i class="fas fa-check"></i> Mark Read</button>
                @endunless
            </div>
        </div>
        @empty
        <div class="table-empty"><p class="table-empty__title">No notifications</p></div>
        @endforelse
    </div>
</div>
@endsection
@push('scripts')
<script>
async function markRead(id, btn) {
    const res = await fetch(`/portal/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
    });
    if (res.ok) {
        btn.closest('.recent-row').style.opacity = '0.75';
        btn.remove();
        typeof AlertSystem !== 'undefined' ? AlertSystem.toast('Notification marked as read.', 'success') : null;
    }
}
</script>
@endpush
