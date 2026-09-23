@extends('layouts.app')

@php($activePage = 'reports')
@php($crumb = __('nav.menu_reports_admin'))

@section('title', __('nav.menu_reports_admin'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ __('reports.eyebrow_admin') }}</p>
        <h1>{{ __('reports.title_admin') }}</h1>
        <p>{{ __('reports.desc_admin') }}</p>
    </div>
    <div class="toolbar-actions">
        <a class="btn btn-soft" href="{{ route('reports.queue', array_merge(request()->query(), ['export' => 'csv'])) }}">{{ __('reports.btn_export_csv') }}</a>
        <button class="btn btn-soft" onclick="window.print()">{{ __('reports.btn_print') }}</button>
    </div>
</div>

<div class="list-screen">
    <div class="list-toolbar">
        <h2>{{ $reports->count() }} {{ __('reports.count_suffix') }}</h2>
    </div>

    @include('reports.partials.filters', ['action' => route('reports.queue')])

    {{-- Admin melihat tabel lengkap dengan nama pelapor + aksi cepat --}}
    <section class="panel admin-table">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('reports.count_suffix') === 'laporan' ? 'Laporan' : '' }}</th>
                        <th>Pelapor</th>
                        <th>Lokasi</th>
                        <th>Tanggal</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
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
                            <td>{{ $report->reporter->name }}</td>
                            <td>{{ $report->location }}</td>
                            <td>{{ $report->incident_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $report->priority }}</td>
                            <td><span class="pill {{ $report->statusPillClass() }}">{{ $report->status }}</span></td>
                            <td class="action-row">
                                <a class="table-action" href="{{ route('reports.show', $report) }}">{{ __('common.btn_see_detail') }} ↗</a>
                                <a class="table-action" href="{{ route('reports.download', $report) }}" download>Unduh</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">{{ __('reports.empty_title') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
