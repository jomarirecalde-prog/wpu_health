@extends('layouts.physician-app')
@section('title', 'My Calendar')
@section('page-heading', 'My Calendar')
@section('page-description', 'Your consultation schedule — month, week, day, and agenda views.')
@section('content')
<div class="content-card wpu-calendar-primary">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calendar-alt"></i> My Calendar</h2>
        <a href="{{ route('physician.schedule') }}" class="btn btn-secondary btn-sm"><i class="fas fa-clock"></i> Manage Schedule</a>
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
