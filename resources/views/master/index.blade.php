@extends('layouts.app')

@php($activePage = 'master')
@php($crumb = __('nav.menu_master'))

@section('title', __('nav.menu_master'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ __('master.eyebrow') }}</p>
        <h1>{{ __('master.title') }}</h1>
        <p>{{ __('master.desc') }}</p>
    </div>
</div>

<div class="master-grid">
    {{-- Lokasi / ruang --}}
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>{{ __('master.locations_title') }}</h2>
                <p>{{ $locations->count() }} {{ __('master.active_count_suffix') }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('master.location.store') }}" style="padding:0 22px 14px;display:flex;gap:8px">
            @csrf
            <input name="name" placeholder="{{ __('master.placeholder_new_location') }}" required>
            <button class="btn btn-primary btn-sm" type="submit">{{ __('common.btn_add') }}</button>
        </form>
        <div class="master-list">
            @foreach ($locations as $location)
                <div class="master-item">
                    <span>{{ $location->name }}</span>
                    <form method="POST" action="{{ route('master.location.destroy', $location) }}"
                          onsubmit="return confirm('Hapus lokasi ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit">{{ __('common.btn_delete') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Kategori kerusakan --}}
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>{{ __('master.categories_title') }}</h2>
                <p>{{ $categories->count() }} {{ __('master.active_count_suffix') }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('master.category.store') }}" style="padding:0 22px 14px;display:flex;gap:8px">
            @csrf
            <input name="name" placeholder="{{ __('master.placeholder_new_category') }}" required>
            <button class="btn btn-primary btn-sm" type="submit">{{ __('common.btn_add') }}</button>
        </form>
        <div class="master-list">
            @foreach ($categories as $category)
                <div class="master-item">
                    <span>{{ $category->name }}</span>
                    <form method="POST" action="{{ route('master.category.destroy', $category) }}"
                          onsubmit="return confirm('Hapus kategori ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit">{{ __('common.btn_delete') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
