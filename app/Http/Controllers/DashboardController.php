<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman: Ringkasan (Dashboard)
 * Tampilan: resources/views/dashboard/index.blade.php
 *
 * Satu controller melayani dua tampilan berbeda (Pelapor vs Admin)
 * lewat pengecekan role, persis seperti dashboardTpl() di versi HTML.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        // Pelapor hanya melihat laporannya sendiri, Admin melihat semua laporan.
        $reports = Report::query()
            ->when(! $isAdmin, fn ($query) => $query->where('reporter_id', $user->id))
            ->orderByWorkflowStatus()
            ->latest('updated_at')
            ->get();

        $counts = [
            'Diajukan' => $reports->where('status', 'Diajukan')->count(),
            'Diproses' => $reports->where('status', 'Diproses')->count(),
            'Selesai' => $reports->where('status', 'Selesai')->count(),
            'Ditolak' => $reports->where('status', 'Ditolak')->count(),
        ];

        // Statistik kartu di bagian atas — beda susunan untuk admin vs pelapor.
        $stats = $isAdmin
            ? [
                ['label' => 'Laporan masuk', 'value' => $counts['Diajukan'], 'note' => 'Perlu diverifikasi'],
                ['label' => 'Sedang diproses', 'value' => $counts['Diproses'], 'note' => 'Perlu update teknisi'],
                ['label' => 'Selesai bulan ini', 'value' => $counts['Selesai'], 'note' => 'Status selesai'],
                ['label' => 'Total laporan', 'value' => $reports->count(), 'note' => 'Seluruh periode'],
            ]
            : [
                ['label' => 'Laporan diajukan', 'value' => $reports->count(), 'note' => 'Seluruh laporanmu'],
                ['label' => 'Sedang diproses', 'value' => $counts['Diproses'], 'note' => 'Sedang ditangani'],
                ['label' => 'Sudah selesai', 'value' => $counts['Selesai'], 'note' => 'Riwayat selesai'],
                ['label' => 'Butuh perhatian', 'value' => $counts['Diajukan'], 'note' => 'Menunggu verifikasi'],
            ];

        $recentReports = $reports->take(5);

        return view('dashboard.index', compact('isAdmin', 'stats', 'recentReports'));
    }
}
