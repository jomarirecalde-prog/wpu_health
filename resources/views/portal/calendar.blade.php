@extends('layouts.portal-app')
@section('title', 'My Calendar')
@section('page-heading', 'My Calendar')
@section('content')
<div class="portal-card wpu-calendar-primary">
    <div class="portal-card__head" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
        <h2><i class="fas fa-calendar-alt"></i> My Calendar</h2>
        <a href="{{ route('portal.book') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Book Consultation</a>
    </div>
    <div class="portal-card__body wpu-calendar-wrap wpu-calendar-wrap--primary">
        <div id="calendar"></div>
    </div>
</div>

@include('partials.wpu-calendar-modals', ['showBookModal' => false])
@endsection
@push('scripts')
@include('partials.fullcalendar-assets')
<script src="{{ $wpuAssets }}/js/wpu-calendar.js?v={{ $wpuCalendarJsV }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    WpuCalendar.init({
        el: '#calendar',
        role: 'portal',
        canBook: false,
        eventDisplay: 'portal',
        height: 680,
        initialView: 'dayGridMonth',
    });
});
</script>
@endpush
