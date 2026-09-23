{{--
    Komponen Topbar. Dipakai di layouts/app.blade.php lewat <x-topbar :crumb="..." />
--}}
@php
    $searchRoute = request()->routeIs('reports.queue') ? route('reports.queue') : route('reports.index');
@endphp

<div class="topbar">
    <div class="topbar-left">
        <button class="icon-btn mobile-menu" id="mobileMenu" aria-label="Buka menu">☰</button>
        <div class="breadcrumb">
            {{ __('nav.breadcrumb_prefix') }} <span>/</span> <strong>{{ $crumb }}</strong>
        </div>
    </div>
    <div class="topbar-actions">
        <form method="GET" action="{{ $searchRoute }}" class="search-form">
            <div class="search">
                <span class="search-icon">⌕</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('nav.search_placeholder') }}" aria-label="{{ __('nav.search_placeholder') }}">
            </div>
        </form>
        <a class="icon-btn" href="{{ route('profile.edit') }}" title="{{ __('nav.profile_icon') }}">◉</a>
    </div>
</div>
