@extends('layouts.app')

@php($activePage = auth()->user()->isAdmin() ? 'reports' : 'reports')
@php($crumb = $report->code)

@section('title', $report->code)

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden !important;
        }

        .app-shell > :not(.main),
        .main > :not(.page),
        .page-screen > :not(.report-print-content) {
            display: none !important;
        }

        .app-shell,
        .main,
        .page,
        .page-screen {
            display: block !important;
            width: auto !important;
            max-width: none !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
        }

        .report-print-content,
        .report-print-content * {
            visibility: visible !important;
        }

        .report-print-content {
            padding: 24px;
            color: #111;
            background: #fff;
        }

        .report-print-content .page-head {
            margin-bottom: 20px;
        }

        .report-print-content .panel {
            max-width: none !important;
            padding: 0 !important;
            border: 0;
            box-shadow: none;
        }

        .report-print-content .no-print {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
@php($isAdmin = auth()->user()->isAdmin())

<div class="report-print-content">
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $report->code }} · {{ $report->category }}</p>
            <h1>{{ $report->title }}</h1>
            <p>Rincian laporan.</p>
        </div>
        <div class="toolbar-actions no-print">
            <a class="btn btn-soft" href="{{ $isAdmin ? route('reports.queue') : route('reports.index') }}">{{ __('common.btn_back') }}</a>
            @if ($isAdmin)
                <a class="btn btn-soft" href="{{ route('reports.download', $report) }}" download>Unduh laporan</a>
                <button class="btn btn-primary" type="button" onclick="window.print()">Cetak laporan</button>
            @endif
        </div>
    </div>

<section class="panel" style="padding:22px;max-width:680px">
    <div class="detail-line">
        <span>Status</span>
        <strong><span class="pill {{ $report->statusPillClass() }}">{{ $report->status }}</span></strong>
    </div>
    <div class="detail-line">
        <span>Pelapor</span>
        <strong>{{ $report->reporter->name }}</strong>
    </div>
    <div class="detail-line">
        <span>Lokasi</span>
        <strong>{{ $report->location }}</strong>
    </div>
    <div class="detail-line">
        <span>Tanggal</span>
        <strong>{{ $report->incident_date->translatedFormat('d M Y') }}</strong>
    </div>
    <div class="detail-line">
        <span>Prioritas</span>
        <strong>{{ $report->priority }}</strong>
    </div>
    <div class="detail-line">
        <span>Deskripsi</span>
        <span>{{ $report->description }}</span>
    </div>
    <div class="detail-line">
        <span>Catatan</span>
        <span>{{ $report->note ?: '-' }}</span>
    </div>
    @if ($report->evidence_path)
        <div class="detail-line">
            <span>Bukti</span>
            <span>
                <a href="{{ asset('storage/' . $report->evidence_path) }}" target="_blank" rel="noopener" class="btn btn-soft btn-sm no-print">Buka foto</a>
                <a href="{{ asset('storage/' . $report->evidence_path) }}" download class="btn btn-soft btn-sm no-print">Unduh foto</a>
            </span>
        </div>
        <div style="margin-top:18px">
            <img src="{{ asset('storage/' . $report->evidence_path) }}" alt="Bukti foto {{ $report->code }}" style="display:block;max-width:100%;max-height:420px;border-radius:8px;object-fit:contain">
        </div>
    @endif

    @if ($isAdmin && $report->status !== 'Selesai')
        {{-- Hanya admin yang bisa mengubah status & menulis catatan tindak lanjut --}}
        <form method="POST" action="{{ route('reports.updateStatus', $report) }}" class="modal-actions no-print" style="margin-top:20px">
            @csrf
            @method('PATCH')
            <select name="status">
                <option value="Diajukan" {{ $report->status === 'Diajukan' ? 'selected' : '' }}>{{ __('common.status_diajukan') }}</option>
                <option value="Diproses" {{ $report->status === 'Diproses' ? 'selected' : '' }}>{{ __('common.status_diproses') }}</option>
                <option value="Selesai" {{ $report->status === 'Selesai' ? 'selected' : '' }}>{{ __('common.status_selesai') }}</option>
                <option value="Ditolak" {{ $report->status === 'Ditolak' ? 'selected' : '' }}>{{ __('common.status_ditolak') }}</option>
            </select>
            <input name="note" placeholder="Catatan petugas" value="{{ $report->note }}">
            <button class="btn btn-primary" type="submit">Simpan status</button>
        </form>
    @elseif ($report->status === 'Selesai')
        <div class="success" role="status" style="margin-top:20px">Laporan selesai dan terverifikasi. Status tidak dapat diubah lagi.</div>
    @endif
</section>
</div>
@endsection

@if (request()->boolean('print'))
    @push('scripts')
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endpush
@endif
