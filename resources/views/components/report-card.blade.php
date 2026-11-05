{{--
    Kartu satu laporan, dipakai di reports/index.blade.php & reports/queue.blade.php
    Pemakaian: <x-report-card :report="$report" />
--}}
<article class="report-card">
    <div class="report-card-head">
        <div>
            <div class="report-card-id">{{ $report->code }}</div>
            <h3>{{ $report->title }}</h3>
        </div>
        <span class="pill {{ $report->statusPillClass() }}">{{ $report->status }}</span>
    </div>
    <p>{{ \Illuminate\Support\Str::limit($report->description, 110) }}</p>
    <div class="report-meta">
        <span>⌖ {{ $report->location }}</span>
        <span>◷ {{ $report->incident_date->translatedFormat('d M Y') }}</span>
        <span>▣ {{ $report->category }}</span>
    </div>
    <div class="report-card-actions">
        <a class="btn btn-soft btn-sm" href="{{ route('reports.show', $report) }}">
            {{ __('common.btn_see_detail') }} ↗
        </a>
        <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Hapus laporan ini secara permanen?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-coral btn-sm" type="submit">Hapus</button>
        </form>
    </div>
</article>
