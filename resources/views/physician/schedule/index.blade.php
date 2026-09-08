@extends('layouts.physician-app')
@section('title', 'My Schedule')
@section('page-heading', 'My Schedule')
@section('content')
<div class="portal-card"><div class="portal-card__head"><i class="fas fa-clock"></i> Recurring Weekly Schedule</div><div class="portal-card__body">
    <p style="font-size:13px;color:var(--gray-500);margin-bottom:12px">Set consultation hours and breaks. Patients cannot book during break periods.</p>
    <form method="post" action="{{ route('physician.schedule.store') }}">@csrf
        @foreach($days as $dayIndex => $dayName)
        <div class="settings-panel" style="margin-bottom:12px">
            <header class="settings-panel__head"><div class="settings-panel__titles"><h3 style="font-size:14px">{{ $dayName }}</h3></div></header>
            <div class="settings-panel__body">
                @php $daySchedules = $physician->schedules->where('day_of_week', $dayIndex)->where('is_break', false); @endphp
                @forelse($daySchedules as $i => $s)
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                    <input type="hidden" name="schedules[{{ $dayIndex }}_{{ $i }}][day_of_week]" value="{{ $dayIndex }}">
                    <input type="time" name="schedules[{{ $dayIndex }}_{{ $i }}][start_time]" class="form-control" style="width:auto" value="{{ substr($s->start_time,0,5) }}">
                    <span style="align-self:center">to</span>
                    <input type="time" name="schedules[{{ $dayIndex }}_{{ $i }}][end_time]" class="form-control" style="width:auto" value="{{ substr($s->end_time,0,5) }}">
                </div>
                @empty
                <div style="display:flex;gap:8px;margin-bottom:8px">
                    <input type="hidden" name="schedules[{{ $dayIndex }}_0][day_of_week]" value="{{ $dayIndex }}">
                    <input type="time" name="schedules[{{ $dayIndex }}_0][start_time]" class="form-control" style="width:auto" value="08:00">
                    <span style="align-self:center">to</span>
                    <input type="time" name="schedules[{{ $dayIndex }}_0][end_time]" class="form-control" style="width:auto" value="17:00">
                </div>
                @endforelse
                @php $break = $physician->schedules->where('day_of_week', $dayIndex)->where('is_break', true)->first(); @endphp
                <p style="font-size:12px;color:var(--gray-500)">Break:
                    <input type="hidden" name="schedules[{{ $dayIndex }}_break][day_of_week]" value="{{ $dayIndex }}">
                    <input type="hidden" name="schedules[{{ $dayIndex }}_break][is_break]" value="1">
                    <input type="time" name="schedules[{{ $dayIndex }}_break][start_time]" class="form-control" style="width:auto;display:inline" value="{{ $break ? substr($break->start_time,0,5) : '12:00' }}"> –
                    <input type="time" name="schedules[{{ $dayIndex }}_break][end_time]" class="form-control" style="width:auto;display:inline" value="{{ $break ? substr($break->end_time,0,5) : '13:00' }}">
                </p>
            </div>
        </div>
        @endforeach
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Schedule</button>
    </form>
</div></div>
<div class="portal-card"><div class="portal-card__head"><i class="fas fa-ban"></i> Block Date / Exception</div><div class="portal-card__body">
    <form method="post" action="{{ route('physician.schedule.exception') }}">@csrf
        <div class="form-group"><label>Date</label><input type="date" name="date" class="form-control" min="{{ date('Y-m-d') }}" required></div>
        <div class="form-group"><label>Type</label><select name="type" class="form-control"><option value="unavailable">Unavailable</option><option value="leave">Leave</option><option value="holiday">Holiday</option><option value="meeting">Meeting</option></select></div>
        <div class="form-group"><label>Reason</label><input name="reason" class="form-control"></div>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-plus"></i> Add Exception</button>
    </form>
    @if($physician->scheduleExceptions->isNotEmpty())<ul style="margin-top:12px;font-size:13px">@foreach($physician->scheduleExceptions as $e)<li>{{ $e->date->format('M j, Y') }} — {{ $e->type }} @if($e->reason)({{ $e->reason }})@endif</li>@endforeach</ul>@endif
</div></div>
@endsection
