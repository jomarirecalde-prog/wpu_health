@extends('layouts.physician-app')
@section('title', 'My Calendar')
@section('page-heading', 'My Calendar')
@section('content')
<div class="portal-card wpu-calendar-primary">
    <div class="portal-card__head" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
        <h2><i class="fas fa-calendar-alt"></i> My Calendar</h2>
        <a href="{{ route('physician.schedule') }}" class="btn btn-secondary btn-sm"><i class="fas fa-clock"></i> Manage Schedule</a>
    </div>
    <div class="portal-card__body wpu-calendar-wrap wpu-calendar-wrap--primary">
        <div id="calendar"></div>
    </div>
</div>

@include('partials.wpu-calendar-modals', ['showBookModal' => false])
@endsection
@push('scripts')
@include('partials.fullcalendar-assets')
<script src="{{ $wpuAssets }}/js/wpu-calendar.js?v={{ $wpuCalendarJsV ?? filemtime(base_path('unified_portal/assets/js/wpu-calendar.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    WpuCalendar.init({
        el: '#calendar',
        role: 'physician',
        canBook: false,
        height: 720,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        scheduleUrl: @json(route('physician.schedule')),
    });
});
</script>
@endpush
