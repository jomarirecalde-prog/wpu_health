@props([
    'user' => null,
    'size' => 'sidebar',
])

@php
    $user = $user ?? auth('portal')->user();
    $initials = $user?->initials() ?? '??';
    $photoUrl = $user?->profilePhotoUrl();
    $sizeClass = match ($size) {
        'topbar' => 'wpu-avatar wpu-avatar--topbar',
        'profile' => 'wpu-avatar wpu-avatar--profile',
        default => 'user-avatar',
    };
@endphp

@if($photoUrl)
    <img {{ $attributes->merge(['class' => $sizeClass.' wpu-avatar--photo']) }} src="{{ $photoUrl }}" alt="" loading="lazy">
@else
    <div {{ $attributes->merge(['class' => $sizeClass]) }} aria-hidden="true">{{ $initials }}</div>
@endif

@once
@push('styles')
<style>
.user-avatar.wpu-avatar--photo, .wpu-avatar--photo { object-fit:cover; padding:0 !important; }
.wpu-avatar { display:inline-flex; align-items:center; justify-content:center; background:var(--secondary-blue, #2563eb); color:#fff; font-weight:700; overflow:hidden; }
.wpu-avatar--topbar { width:28px !important; height:28px !important; border-radius:8px !important; font-size:11px !important; min-width:28px; }
.wpu-avatar--profile { width:88px; height:88px; border-radius:16px; font-size:28px; }
.topbar-profile-btn .av { overflow:hidden; padding:0; }
.topbar-profile-btn .av.wpu-avatar--photo, .topbar-profile-btn img.wpu-avatar--topbar { background:transparent !important; box-shadow:none !important; }
</style>
@endpush
@endonce
