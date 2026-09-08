@props(['status'])

@php
    $enum = $status instanceof \App\Enums\AppointmentStatus ? $status : \App\Enums\AppointmentStatus::tryFrom((string) $status);
    if (!$enum) { $enum = \App\Enums\AppointmentStatus::Pending; }
@endphp
<span class="status-badge status-badge--{{ str_replace('_', '_', $enum->value) }}" title="{{ $enum->label() }}">
    <i class="fas fa-{{ $enum->icon() }}" aria-hidden="true"></i>
    {{ $enum->label() }}
</span>
