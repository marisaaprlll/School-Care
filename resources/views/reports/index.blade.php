@extends('layouts.app')

@php($activePage = 'reports')
@php($crumb = __('nav.menu_reports_reporter'))

@section('title', __('nav.menu_reports_reporter'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ __('reports.eyebrow_reporter') }}</p>
        <h1>{{ __('reports.title_reporter') }}</h1>
        <p>{{ __('reports.desc_reporter') }}</p>
    </div>
    <div class="toolbar-actions">
        <a class="btn btn-soft" href="{{ route('reports.index', array_merge(request()->query(), ['export' => 'csv'])) }}">{{ __('reports.btn_export_csv') }}</a>
        <a class="btn btn-primary" href="{{ route('reports.create') }}">{{ __('reports.btn_new_report') }}</a>
    </div>
</div>

<div class="list-screen">
    <div class="list-toolbar">
        <h2>{{ $reports->count() }} {{ __('reports.count_suffix') }}</h2>
    </div>

    @include('reports.partials.filters', ['action' => route('reports.index')])

    <div class="report-grid">
        @forelse ($reports as $report)
            <x-report-card :report="$report" />
        @empty
            <div class="panel empty-state" style="grid-column:1/-1">
                <strong>{{ __('reports.empty_title') }}</strong>
                <span>{{ __('reports.empty_desc') }}</span>
            </div>
        @endforelse
    </div>
</div>
@endsection
