@extends('layouts.his-admin')
@section('title', 'Portal Users')
@section('page-heading', 'Portal Users')
@section('page-description', 'Patient accounts registered for consultation booking.')
@php $activeSection = 'portal-users'; @endphp

@section('content')
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-users"></i> Registered Users</h2>
    </div>
    <div class="search-section cr-toolbar">
        <form method="get" action="{{ route('admin.calendar.portal-users') }}" class="cr-search-row" id="portal-users-filter" style="display:flex;flex-wrap:wrap;gap:12px;width:100%;align-items:flex-end">
            <div class="form-group search-box" style="margin:0;flex:1;min-width:min(100%,240px)">
                <label for="portal-users-search" class="sr-only">Search patients</label>
                <i class="fas fa-search search-icon" aria-hidden="true"></i>
                <input type="search" name="q" id="portal-users-search" class="form-control search-input" value="{{ request('q') }}" placeholder="Search patients..." autocomplete="off">
            </div>
            <div class="form-group" style="margin:0;min-width:140px">
                <label for="portal-users-status">Status</label>
                <select name="status" id="portal-users-status" class="form-control">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            @if($patientTypes->isNotEmpty())
            <div class="form-group" style="margin:0;min-width:160px">
                <label for="portal-users-type">Patient Type</label>
                <select name="patient_type_id" id="portal-users-type" class="form-control">
                    <option value="">All</option>
                    @foreach($patientTypes as $type)
                        <option value="{{ $type->id }}" @selected((string) request('patient_type_id') === (string) $type->id)>{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <noscript>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            </noscript>
        </form>
    </div>
    <div class="card-body" style="padding:0" id="portal-users-results">
        @include('admin.calendar.partials.portal-users-results')
    </div>
</div>
@endsection

@push('styles')
<style>
.sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
.portal-user-cell { display:flex; align-items:center; gap:10px; min-width:0; }
.portal-user-cell__name { font-weight:600; color:inherit; }
.table-actions { display:flex; gap:6px; flex-wrap:wrap; }
.search-section .search-box { position:relative; }
.search-section .search-box .search-icon { left:14px; }
.search-section .search-box .search-input { padding-left:42px; }
@media (max-width: 640px) {
    .data-table tbody td[data-label="Actions"] { display:block; }
    .data-table tbody td[data-label="Actions"] .table-actions { justify-content:flex-end; margin-top:4px; }
    .data-table tbody td[data-label="Actions"]::before { display:none; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('portal-users-filter');
    var results = document.getElementById('portal-users-results');
    var search = document.getElementById('portal-users-search');
    if (!form || !results) return;

    function load(url) {
        var fetchFn = (window.WpuAjax && WpuAjax.fetch) ? WpuAjax.fetch : fetch;
        return fetchFn(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        }).then(function (response) {
            return response.text();
        }).then(function (html) {
            results.innerHTML = html;
            bindPagination();
        }).catch(function () {
            window.location.href = url;
        });
    }

    function currentUrl() {
        var params = new URLSearchParams(new FormData(form));
        ['q', 'status', 'patient_type_id'].forEach(function (key) {
            if (!params.get(key)) params.delete(key);
        });
        var qs = params.toString();
        return form.action + (qs ? '?' + qs : '');
    }

    function bindPagination() {
        results.querySelectorAll('.pagination a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                history.replaceState({}, '', link.href);
                load(link.href);
            });
        });
    }

    function refresh() {
        var url = currentUrl();
        history.replaceState({}, '', url);
        load(url);
    }

    if (search) {
        search.addEventListener('input', function () {
            if (window.WpuAjax && WpuAjax.debounce) {
                WpuAjax.debounce('portal-users-search', refresh, 300);
            } else {
                refresh();
            }
        });
    }

    form.querySelectorAll('select').forEach(function (select) {
        select.addEventListener('change', refresh);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        refresh();
    });

    bindPagination();
})();
</script>
@endpush
