<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportStatusOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_lists_follow_the_requested_status_order(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['Ditolak', 'Selesai', 'Diproses', 'Diajukan'] as $status) {
            Report::create([
                'code' => 'SC-'.str_pad((string) (Report::count() + 1), 4, '0', STR_PAD_LEFT),
                'title' => 'Judul '.$status,
                'location' => 'Lab Komputer 1',
                'category' => 'Elektronik',
                'incident_date' => '2026-10-20',
                'status' => $status,
                'priority' => 'Normal',
                'description' => 'Deskripsi '.$status,
                'reporter_id' => $reporter->id,
            ]);
        }

        $expectedOrder = ['Judul Diajukan', 'Judul Diproses', 'Judul Selesai', 'Judul Ditolak'];

        $this->actingAs($admin)
            ->get(route('reports.queue'))
            ->assertOk()
            ->assertSee('Unduh')
            ->assertSee('Cetak')
            ->assertSee('class="btn btn-soft btn-sm"', false)
            ->assertSeeInOrder($expectedOrder);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(asset('denah.jpeg'), false)
            ->assertSeeInOrder($expectedOrder);

        $this->actingAs($reporter)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeInOrder($expectedOrder);
    }
}
