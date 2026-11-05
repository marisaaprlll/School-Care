@extends('layouts.app')

@php($activePage = 'dashboard')
@php($crumb = __('nav.menu_dashboard'))

@section('title', __('nav.menu_dashboard'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ $isAdmin ? __('dashboard.eyebrow_admin') : __('dashboard.eyebrow_reporter') }} · 09 September 2026</p>
        <h1>{{ $isAdmin ? __('dashboard.title_admin') : __('dashboard.title_reporter') }}</h1>
        <p>{{ $isAdmin ? __('dashboard.lead_admin') : __('dashboard.lead_reporter') }}</p>
    </div>
    <a class="btn btn-primary" href="{{ $isAdmin ? route('reports.queue') : route('reports.create') }}">
        {{ $isAdmin ? __('dashboard.cta_admin') : __('dashboard.cta_reporter') }}
    </a>
</div>

{{-- Hero banner --}}
<div class="hero">
    <div>
        <p class="eyebrow">{{ __('dashboard.hero_eyebrow') }}</p>
        <h2>{{ $isAdmin ? __('dashboard.hero_title_admin') : __('dashboard.hero_title_reporter') }}</h2>
        <p>{{ $isAdmin ? __('dashboard.hero_desc_admin') : __('dashboard.hero_desc_reporter') }}</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="{{ $isAdmin ? route('reports.queue') : route('reports.create') }}">
                {{ $isAdmin ? __('dashboard.hero_action_primary_admin') : __('dashboard.hero_action_primary_reporter') }}
            </a>
            <a class="btn btn-soft" href="{{ $isAdmin ? route('reports.queue') : route('reports.index') }}">
                {{ $isAdmin ? __('dashboard.hero_action_secondary_admin') : __('dashboard.hero_action_secondary_reporter') }}
            </a>
        </div>
    </div>
    <div class="hero-side">
        @php($first = $recentReports->first())
        <div class="mini-ticket">
            <div class="mini-ticket-top">
                <span class="mini-ticket-id">{{ $first?->code ?? '—' }}</span>
                <span class="pill {{ $first?->statusPillClass() ?? 'pill-gray' }}">{{ $first?->status ?? '—' }}</span>
            </div>
            <h3>{{ $first?->title ?? 'Belum ada laporan' }}</h3>
            <p>{{ $first?->location ?? '—' }} · Prioritas {{ $first?->priority ?? '—' }}</p>
            <div class="mini-ticket-footer">
                <span>Progress penanganan</span>
                <strong>{{ $first?->status === 'Selesai' ? 100 : ($first?->status === 'Diproses' ? 60 : 20) }}%</strong>
            </div>
            <div class="progress">
                <span style="width:{{ $first?->status === 'Selesai' ? 100 : ($first?->status === 'Diproses' ? 60 : 20) }}%"></span>
            </div>
        </div>
    </div>
</div>

{{-- Kartu statistik --}}
<div class="dashboard-summary">
    <div class="stats">
        @foreach ($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" />
        @endforeach
    </div>
    <section class="panel summary-map">
        <h2>Denah Sekolah</h2>
        <a href="{{ asset('denah.jpeg') }}" target="_blank" rel="noopener">
            <img src="{{ asset('denah.jpeg') }}" alt="Denah lokasi dan ruang sekolah">
        </a>
    </section>
</div>

{{-- Tabel laporan terbaru --}}
<div class="content-grid">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>{{ $isAdmin ? __('dashboard.table_title_admin') : __('dashboard.table_title_reporter') }}</h2>
                <p>{{ $isAdmin ? __('dashboard.table_desc_admin') : __('dashboard.table_desc_reporter') }}</p>
            </div>
            <a class="btn btn-soft btn-sm" href="{{ $isAdmin ? route('reports.queue') : route('reports.index') }}">
                {{ __('common.btn_see_all') }} ↗
            </a>
        </div>
        <div class="panel-body">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('dashboard.table_col_report') }}</th>
                            <th>{{ __('dashboard.table_col_location') }}</th>
                            <th>{{ __('dashboard.table_col_date') }}</th>
                            <th>{{ __('dashboard.table_col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentReports as $report)
                            <tr>
                                <td>
                                    <div class="report-title">
                                        <span class="report-dot" style="background:var(--{{ ['Diajukan'=>'blue','Diproses'=>'yellow','Selesai'=>'mint','Ditolak'=>'coral'][$report->status] ?? 'line' }})"></span>
                                        <div>
                                            <strong>{{ $report->title }}</strong>
                                            <small>{{ $report->code }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $report->location }}</td>
                                <td>{{ $report->incident_date->translatedFormat('d M Y') }}</td>
                                <td><span class="pill {{ $report->statusPillClass() }}">{{ $report->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted">{{ __('reports.empty_title') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
