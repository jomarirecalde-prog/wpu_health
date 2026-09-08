@extends('layouts.his-admin')
@section('title', 'Appointment Settings')
@section('page-heading', 'Appointment Settings')
@section('page-description', 'Configure cancellation rules, booking deadlines, and reminders.')
@php $activeSection = 'settings'; @endphp
@section('content')
<div class="content-card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-cog"></i> Settings</h2></div>
    <div class="card-body">
        <form method="post" action="{{ route('admin.calendar.settings.update') }}" style="max-width:480px">@csrf @method('PUT')
            <div class="form-group"><label>Cancellation allowed until (hours before)</label><input type="number" name="cancellation_hours_before" class="form-control" value="{{ $settings['cancellation_hours_before'] ?? 2 }}" required></div>
            <div class="form-group"><label>Booking deadline (hours before slot)</label><input type="number" name="booking_deadline_hours" class="form-control" value="{{ $settings['booking_deadline_hours'] ?? 1 }}" required></div>
            <div class="form-group"><label>Reminder hours (comma-separated)</label><input type="text" name="reminder_hours" class="form-control" value="{{ is_array($settings['reminder_hours'] ?? null) ? implode(',', $settings['reminder_hours']) : '24,1' }}" required></div>
            <div class="form-group"><label>Default consultation duration (minutes)</label><input type="number" name="default_consultation_duration" class="form-control" value="{{ $settings['default_consultation_duration'] ?? 30 }}" required></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>
@endsection
