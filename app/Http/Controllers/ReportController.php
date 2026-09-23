<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Halaman: Laporan Saya (Pelapor)
     * Tampilan: resources/views/reports/index.blade.php
     * Hanya menampilkan laporan milik user yang sedang login.
     */
    public function index(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $this->reportFilters($request);
        $reports = $this->filteredReports($request, false)
            ->latest('updated_at')
            ->get();

        if ($request->boolean('export') || $request->get('export') === 'csv') {
            return $this->exportCsv($reports, 'laporan-saya.csv');
        }

        return view('reports.index', [
            'reports' => $reports,
            'isAdmin' => false,
            ...$filters,
            'locations' => Location::orderBy('name')->pluck('name'),
            'categories' => Category::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Halaman: Antrean Laporan (Admin)
     * Tampilan: resources/views/reports/queue.blade.php
     * Menampilkan seluruh laporan dari semua pelapor.
     */
    public function queue(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $this->reportFilters($request);
        $reports = $this->filteredReports($request, true)
            ->latest('updated_at')
            ->get();

        if ($request->boolean('export') || $request->get('export') === 'csv') {
            return $this->exportCsv($reports, 'antrean-laporan.csv');
        }

        return view('reports.queue', [
            'reports' => $reports,
            'isAdmin' => true,
            ...$filters,
            'locations' => Location::orderBy('name')->pluck('name'),
            'categories' => Category::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Halaman: Ajukan Laporan (Pelapor)
     * Tampilan: resources/views/reports/create.blade.php
     */
    public function create(): View
    {
        $locations = Location::orderBy('name')->pluck('name');
        $categories = Category::orderBy('name')->pluck('name');

        return view('reports.create', compact('locations', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location' => ['required', 'string'],
            'category' => ['required', 'string'],
            'incident_date' => ['required', 'date'],
            'priority' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'evidence' => ['required', 'image', 'max:1024'], // maks 1 MB, sama seperti versi HTML
        ], [], [
            'location' => 'lokasi',
            'category' => 'kategori',
            'incident_date' => 'tanggal kejadian',
            'title' => 'judul laporan',
            'description' => 'deskripsi',
            'evidence' => 'foto bukti',
        ]);

        $evidencePath = $request->file('evidence')->store('reports', 'public');

        Report::create([
            'code' => $this->generateCode(),
            'title' => $data['title'],
            'location' => $data['location'],
            'category' => $data['category'],
            'incident_date' => $data['incident_date'],
            'status' => 'Diajukan',
            'priority' => $data['priority'] ?? 'Normal',
            'description' => $data['description'],
            'evidence_path' => $evidencePath,
            'reporter_id' => $request->user()->id,
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'Laporan berhasil dikirim. Menunggu verifikasi petugas Sarpras.');
    }

    /**
     * Halaman detail laporan (dipakai Pelapor & Admin).
     * Tampilan: resources/views/reports/show.blade.php
     */
    public function show(Request $request, Report $report): View
    {
        abort_unless(
            $request->user()->isAdmin() || $report->reporter_id === $request->user()->id,
            403
        );

        return view('reports.show', compact('report'));
    }

    public function download(Request $request, Report $report): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(
            $request->user()->isAdmin() || $report->reporter_id === $request->user()->id,
            403
        );

        $report->load('reporter');

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Kode', 'Judul', 'Lokasi', 'Kategori', 'Tanggal Kejadian', 'Prioritas', 'Status', 'Deskripsi', 'Catatan', 'Pelapor', 'Bukti']);
            fputcsv($handle, [
                $report->code,
                $report->title,
                $report->location,
                $report->category,
                optional($report->incident_date)->format('Y-m-d'),
                $report->priority,
                $report->status,
                $report->description,
                $report->note,
                $report->reporter?->name ?? '-',
                $report->evidence_path ? asset('storage/' . $report->evidence_path) : '-',
            ]);
            fclose($handle);
        }, $report->code . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Admin mengubah status laporan (Verifikasi, Proses, Selesai, Tolak)
     * beserta catatan tindak lanjut.
     */
    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:Diajukan,Diproses,Selesai,Ditolak'],
            'note' => ['nullable', 'string'],
        ]);

        $report->update($data);

        return back()->with('success', "Status {$report->code} diperbarui menjadi {$report->status}.");
    }

    private function exportCsv($reports, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($reports) {
            $handle = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Kode', 'Judul', 'Lokasi', 'Kategori', 'Tanggal Kejadian', 'Prioritas', 'Status', 'Deskripsi', 'Catatan', 'Pelapor']);

            foreach ($reports as $report) {
                fputcsv($handle, [
                    $report->code,
                    $report->title,
                    $report->location,
                    $report->category,
                    optional($report->incident_date)->format('Y-m-d'),
                    $report->priority,
                    $report->status,
                    $report->description,
                    $report->note,
                    $report->reporter?->name ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredReports(Request $request, bool $includeReporter)
    {
        $filters = $this->reportFilters($request);
        $search = $filters['searchTerm'];

        return Report::query()
            ->when(! $includeReporter, fn ($query) => $query->where('reporter_id', $request->user()->id))
            ->when($filters['activeStatus'] !== 'Semua', fn ($query) => $query->where('status', $filters['activeStatus']))
            ->when($filters['location'] !== '', fn ($query) => $query->where('location', $filters['location']))
            ->when($filters['category'] !== '', fn ($query) => $query->where('category', $filters['category']))
            ->when($filters['priority'] !== '', fn ($query) => $query->where('priority', $filters['priority']))
            ->when($filters['normalizedDate'] !== '', fn ($query) => $query->whereDate('incident_date', $filters['normalizedDate']))
            ->when($filters['day'] !== '', fn ($query) => $query->whereDay('incident_date', $filters['day']))
            ->when($filters['month'] !== '', fn ($query) => $query->whereMonth('incident_date', $filters['month']))
            ->when($filters['year'] !== '', fn ($query) => $query->whereYear('incident_date', $filters['year']))
            ->when($search !== '', function ($query) use ($search, $includeReporter) {
                $query->where(function ($subQuery) use ($search, $includeReporter) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");

                    if ($includeReporter) {
                        $subQuery->orWhereHas('reporter', fn ($reporterQuery) => $reporterQuery->where('name', 'like', "%{$search}%"));
                    }
                });
            })
            ->when($includeReporter, fn ($query) => $query->with('reporter'));
    }

    private function reportFilters(Request $request): array
    {
        return [
            'activeStatus' => $request->query('status', 'Semua'),
            'searchTerm' => trim((string) $request->query('q', '')),
            'location' => trim((string) $request->query('location', '')),
            'category' => trim((string) $request->query('category', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'date' => trim((string) $request->query('date', '')),
            'normalizedDate' => $this->normalizeDate((string) $request->query('date', '')),
            'day' => $this->normalizeDay((string) $request->query('date', '')),
            'month' => (string) $request->query('month', ''),
            'year' => (string) $request->query('year', ''),
        ];
    }

    private function normalizeDate(string $date): string
    {
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, trim($date));

                if ($parsed && $parsed->format($format) === trim($date)) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Format tanggal yang salah diabaikan, filter lain tetap berjalan.
            }
        }

        return '';
    }

    private function normalizeDay(string $date): string
    {
        $date = trim($date);

        if (preg_match('/^(?:0?[1-9]|[12][0-9]|3[01])$/', $date)) {
            return (string) ((int) $date);
        }

        return '';
    }

    private function generateCode(): string
    {
        $last = Report::orderByDesc('id')->value('id') ?? 240;

        return 'SC-' . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
