<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Report model — satu baris = satu tiket pengaduan sarana/prasarana.
 *
 * Status yang dipakai di seluruh aplikasi: Diajukan, Diproses, Selesai, Ditolak.
 * Prioritas: Normal, Urgent (mengganggu kegiatan), Darurat (berisiko keselamatan).
 */
class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'location',
        'category',
        'incident_date',
        'status',
        'priority',
        'description',
        'note',
        'evidence_path',
        'reporter_id',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
        ];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function scopeOrderByWorkflowStatus(Builder $query): Builder
    {
        return $query->orderByRaw(
            "CASE status WHEN 'Diajukan' THEN 0 WHEN 'Diproses' THEN 1 WHEN 'Selesai' THEN 2 WHEN 'Ditolak' THEN 3 ELSE 4 END"
        );
    }

    /** Kelas CSS pill status, dipakai di Blade: {{ $report->statusPillClass() }} */
    public function statusPillClass(): string
    {
        return match ($this->status) {
            'Diajukan' => 'pill-blue',
            'Diproses' => 'pill-yellow',
            'Selesai' => 'pill-mint',
            'Ditolak' => 'pill-coral',
            default => 'pill-gray',
        };
    }
}
