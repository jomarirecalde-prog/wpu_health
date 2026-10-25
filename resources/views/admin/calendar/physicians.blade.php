@extends('layouts.his-admin')
@section('title', 'Doctors Appointment')
@section('page-heading', 'Doctors Appointment')
@section('page-description', 'Manage physician, dentist, and other provider appointments and availability.')
@php
    $activeSection = 'physicians';
    $providerCategory = request()->query('category', 'physician');
    if (! in_array($providerCategory, ['physician', 'dentist', 'others'], true)) {
        $providerCategory = 'physician';
    }
    if ($errors->any() && old('provider_category') && in_array(old('provider_category'), ['physician', 'dentist', 'others'], true)) {
        $providerCategory = old('provider_category');
    }
@endphp
@section('content')
<div class="cr-tabs" role="tablist" aria-label="Provider category" style="margin-bottom:1rem">
    <button type="button" role="tab" id="provider-tab-physician"
            class="cr-tab {{ $providerCategory === 'physician' ? 'is-active' : '' }}"
            aria-selected="{{ $providerCategory === 'physician' ? 'true' : 'false' }}"
            aria-controls="provider-panel-physician"
            data-provider-category="physician">
        <i class="fas fa-user-md" aria-hidden="true"></i> Physician
    </button>
    <button type="button" role="tab" id="provider-tab-dentist"
            class="cr-tab {{ $providerCategory === 'dentist' ? 'is-active' : '' }}"
            aria-selected="{{ $providerCategory === 'dentist' ? 'true' : 'false' }}"
            aria-controls="provider-panel-dentist"
            data-provider-category="dentist">
        <i class="fas fa-tooth" aria-hidden="true"></i> Dentist
    </button>
    <button type="button" role="tab" id="provider-tab-others"
            class="cr-tab {{ $providerCategory === 'others' ? 'is-active' : '' }}"
            aria-selected="{{ $providerCategory === 'others' ? 'true' : 'false' }}"
            aria-controls="provider-panel-others"
            data-provider-category="others">
        <i class="fas fa-calendar-plus" aria-hidden="true"></i> Others
    </button>
</div>

<div id="provider-panel-physician" class="cr-tab-panel" role="tabpanel" aria-labelledby="provider-tab-physician"
     @if($providerCategory !== 'physician') hidden @endif>
    @include('admin.calendar.partials.provider-category-panel', [
        'category' => 'physician',
        'addIcon' => '<i class="fas fa-user-md"></i>',
        'addTitle' => 'Add Physician',
        'submitIcon' => '<i class="fas fa-plus"></i>',
        'submitLabel' => 'Add Physician',
        'listTitle' => 'Physicians',
        'nameColumn' => 'Physician',
        'titlePlaceholder' => 'Dr.',
        'providers' => $physicians,
    ])
</div>

<div id="provider-panel-dentist" class="cr-tab-panel" role="tabpanel" aria-labelledby="provider-tab-dentist"
     @if($providerCategory !== 'dentist') hidden @endif>
    @include('admin.calendar.partials.provider-category-panel', [
        'category' => 'dentist',
        'addIcon' => '<i class="fas fa-tooth"></i>',
        'addTitle' => 'Add Dentist',
        'submitIcon' => '<i class="fas fa-plus"></i>',
        'submitLabel' => 'Add Dentist',
        'listTitle' => 'Dentists',
        'nameColumn' => 'Dentist',
        'titlePlaceholder' => 'DMD / DDS',
        'specializationPlaceholder' => 'General Dentistry',
        'defaultSpecialization' => '',
        'providers' => $dentists,
    ])
</div>

<div id="provider-panel-others" class="cr-tab-panel" role="tabpanel" aria-labelledby="provider-tab-others"
     @if($providerCategory !== 'others') hidden @endif>
    @include('admin.calendar.partials.provider-category-panel', [
        'category' => 'others',
        'addIcon' => '<i class="fas fa-calendar-plus"></i>',
        'addTitle' => 'Add Provider',
        'submitIcon' => '<i class="fas fa-plus"></i>',
        'submitLabel' => 'Add Provider',
        'listTitle' => 'Other providers',
        'nameColumn' => 'Provider',
        'titlePlaceholder' => 'RN, RPsy, etc.',
        'specializationPlaceholder' => 'Counseling, nursing, etc.',
        'providers' => $otherProviders,
    ])
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tabButtons = document.querySelectorAll('.cr-tabs [role="tab"][data-provider-category]');
    var panels = {
        physician: document.getElementById('provider-panel-physician'),
        dentist: document.getElementById('provider-panel-dentist'),
        others: document.getElementById('provider-panel-others')
    };

    function switchProviderCategory(category) {
        if (!panels[category]) {
            category = 'physician';
        }

        tabButtons.forEach(function (el) {
            var active = el.getAttribute('data-provider-category') === category;
            el.classList.toggle('is-active', active);
            el.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        Object.keys(panels).forEach(function (key) {
            if (panels[key]) {
                panels[key].hidden = key !== category;
            }
        });

        var url = new URL(window.location.href);
        if (category === 'physician') {
            url.searchParams.delete('category');
        } else {
            url.searchParams.set('category', category);
        }
        window.history.replaceState({}, '', url.toString());
    }

    tabButtons.forEach(function (el) {
        el.addEventListener('click', function () {
            switchProviderCategory(el.getAttribute('data-provider-category'));
        });
    });
})();
</script>
@endpush
