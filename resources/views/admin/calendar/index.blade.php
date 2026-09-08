@extends('layouts.his-admin')
@section('title', 'Calendar')
@section('page-heading', 'Calendar')
@section('page-description', 'Centralized consultation schedule — month, week, day, and agenda views.')
@php $activeSection = 'calendar'; @endphp
@section('content')
<div class="content-card wpu-calendar-primary">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-calendar-alt"></i> Consultation Calendar</h2>
        <a href="{{ route('admin.calendar.appointments') }}" class="btn btn-secondary btn-sm"><i class="fas fa-list"></i> Manage Appointments</a>
    </div>
    <div class="calendar-filters">
        <div class="form-group">
            <label for="filter-physician">Physician</label>
            <select id="filter-physician" class="form-control">
                <option value="">All Physicians</option>
                @foreach($physicians as $p)
                    <option value="{{ $p->id }}">{{ $p->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filter-department">Department</label>
            <select id="filter-department" class="form-control">
                <option value="">All</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filter-status">Status</label>
            <select id="filter-status" class="form-control">
                <option value="">All</option>
                @foreach(['pending','confirmed','completed','cancelled','rejected','no_show'] as $s)
                    <option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filter-type">Consultation</label>
            <select id="filter-type" class="form-control">
                <option value="">All</option>
                @foreach($consultationTypes as $k=>$v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card-body wpu-calendar-wrap wpu-calendar-wrap--primary">
        <div id="calendar"></div>
    </div>
</div>

<details class="content-card wpu-calendar-stats-collapsible" style="margin-top:18px">
    <summary class="card-header" style="cursor:pointer;list-style:none">
        <h2 class="card-title" style="margin:0"><i class="fas fa-chart-bar"></i> Today&apos;s Summary</h2>
    </summary>
    <div class="card-body">
        <div class="stats-grid dashboard-stats">
            @foreach(['today_total'=>'Today','today_pending'=>'Pending','today_confirmed'=>'Confirmed','today_completed'=>'Completed','today_cancelled'=>'Cancelled','today_no_show'=>'No-show'] as $k=>$label)
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value">{{ $stats[$k] ?? 0 }}</div>
                        <div class="stat-label">{{ $label }}</div>
                    </div>
                    <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</details>

@include('partials.wpu-calendar-modals', [
    'showBookModal' => true,
    'physicians' => $physicians,
    'portalUsers' => $portalUsers,
    'consultationTypes' => $consultationTypes,
])
@endsection
@push('scripts')
@include('partials.fullcalendar-assets')
<script src="{{ $wpuAssets }}/js/wpu-calendar.js?v={{ $wpuCalendarJsV }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof WpuCalendar === 'undefined') {
        console.error('WpuCalendar script failed to load.');
        return;
    }
    WpuCalendar.init({
        el: '#calendar',
        role: 'admin',
        canBook: true,
        height: 720,
        filterMap: {
            'filter-physician': 'physician_id',
            'filter-department': 'department_id',
            'filter-status': 'status',
            'filter-type': 'consultation_type',
        },
        consultationTypes: @json($consultationTypes),
    });
});
</script>
@endpush
