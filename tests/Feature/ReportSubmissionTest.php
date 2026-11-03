<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporter_can_submit_a_report_without_an_evidence_photo(): void
    {
        $reporter = User::factory()->create([
            'role' => 'reporter',
            'class_name' => 'XII RPL B',
        ]);

        $this->actingAs($reporter)
            ->post(route('reports.store'), [
                'location' => 'Lab Komputer 1',
                'category' => 'Elektronik',
                'incident_date' => '2026-10-20',
                'priority' => 'Normal',
                'title' => 'Lampu lab mati',
                'description' => 'Lampu di bagian belakang lab tidak menyala.',
            ])
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'title' => 'Lampu lab mati',
            'status' => 'Diajukan',
            'evidence_path' => null,
        ]);

        $this->assertSame(1, Report::count());
    }
}
