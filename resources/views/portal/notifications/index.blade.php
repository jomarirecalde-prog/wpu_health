@extends('layouts.portal-app')
@section('title', 'Notifications')
@section('page-heading', 'Notifications')
@section('content')
<div class="portal-card"><div class="portal-card__body">
@forelse($notifications as $n)
<div class="recent-row" style="padding:10px 0;border-bottom:1px solid var(--gray-200);{{ $n->read_at?'opacity:.75':'' }}">
    <div class="meta"><strong>{{ $n->title }}</strong><span>{{ $n->message }}</span></div>
    <span class="status-pill">{{ $n->created_at->diffForHumans() }}</span>
</div>
@empty<div class="table-empty"><p class="table-empty__title">No notifications</p></div>@endforelse
</div></div>
@endsection
