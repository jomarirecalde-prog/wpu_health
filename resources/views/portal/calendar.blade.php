@extends('layouts.portal-app')
@section('title', 'My Calendar')
@section('page-heading', 'My Calendar')
@section('page-description', 'View your consultation schedule — month, week, day, and agenda views.')
@section('content')
<div class="content-card wpu-calendar-primary">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calendar-alt"></i> My Calendar</h2>
        <a href="{{ route('portal.book') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Book Consultation</a>
    </div>
    <div class="card-body wpu-calendar-wrap wpu-calendar-wrap--primary">
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
