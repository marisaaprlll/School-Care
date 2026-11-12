<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
                'priority' => 'Darurat',
                'title' => 'Lampu lab mati',
                'description' => 'Lampu di bagian belakang lab tidak menyala.',
            ])
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'title' => 'Lampu lab mati',
            'status' => 'Diajukan',
            'priority' => 'Belum dinilai',
            'evidence_path' => null,
        ]);

        $this->assertSame(1, Report::count());
    }

    public function test_report_evidence_is_displayed_without_a_public_storage_link(): void
    {
        Storage::fake('public');
        $reporter = User::factory()->create(['role' => 'reporter']);
        $pngChunk = static fn (string $type, string $data): string => pack('N', strlen($data))
            .$type
            .$data
            .pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n"
            .$pngChunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0))
            .$pngChunk('IDAT', gzcompress("\x00\xff\x00\x00"))
            .$pngChunk('IEND', '');

        $this->actingAs($reporter)
            ->post(route('reports.store'), [
                'location' => 'Lab Komputer 1',
                'category' => 'Elektronik',
                'incident_date' => '2026-10-20',
                'title' => 'Lampu lab mati',
                'description' => 'Lampu di bagian belakang lab tidak menyala.',
                'evidence' => UploadedFile::fake()->createWithContent('bukti.png', $png),
            ])
            ->assertRedirect(route('reports.index'));

        $report = Report::firstOrFail();
        Storage::disk('public')->assertExists($report->evidence_path);

        $this->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee(route('reports.evidence', $report), false);

        $this->get(route('reports.evidence', $report))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get(route('reports.evidence', ['report' => $report, 'download' => 1]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename='.basename($report->evidence_path));

        $otherReporter = User::factory()->create(['role' => 'reporter']);

        $this->actingAs($otherReporter)
            ->get(route('reports.evidence', $report))
            ->assertForbidden();
    }

    public function test_reporter_can_delete_their_report_and_its_evidence(): void
    {
        Storage::fake('public');
        $reporter = User::factory()->create(['role' => 'reporter']);
        $report = $this->createReport($reporter);
        $report->update(['evidence_path' => 'reports/evidence.jpg']);
        Storage::disk('public')->put($report->evidence_path, 'image contents');

        $this->actingAs($reporter)
            ->delete(route('reports.destroy', $report))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        Storage::disk('public')->assertMissing('reports/evidence.jpg');
    }

    public function test_reporter_cannot_delete_another_reporters_report(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);
        $owner = User::factory()->create(['role' => 'reporter']);
        $report = $this->createReport($owner);

        $this->actingAs($reporter)
            ->delete(route('reports.destroy', $report))
            ->assertForbidden();

        $this->assertDatabaseHas('reports', ['id' => $report->id]);
    }

    public function test_report_submission_form_does_not_offer_a_priority_choice(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);

        $this->actingAs($reporter)
            ->get(route('reports.create'))
            ->assertOk()
            ->assertDontSee('name="priority"', false);
    }

    public function test_only_admin_can_assign_report_priority(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->createReport($reporter);

        $this->actingAs($reporter)
            ->patch(route('reports.updateStatus', $report), [
                'priority' => 'Darurat',
                'status' => 'Diproses',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'priority' => 'Belum dinilai',
        ]);

        $this->actingAs($admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.updateStatus', $report), [
                'priority' => 'Darurat',
                'status' => 'Diproses',
            ])
            ->assertRedirect(route('reports.show', $report))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'priority' => 'Darurat',
            'status' => 'Diproses',
        ]);
    }

    private function createReport(User $reporter): Report
    {
        return Report::create([
            'code' => 'SC-'.str_pad((string) (Report::count() + 1), 4, '0', STR_PAD_LEFT),
            'title' => 'Lampu ruang mati',
            'location' => 'Lab Komputer 1',
            'category' => 'Elektronik',
            'incident_date' => '2026-10-20',
            'status' => 'Diajukan',
            'priority' => 'Belum dinilai',
            'description' => 'Lampu tidak menyala.',
            'reporter_id' => $reporter->id,
        ]);
    }
}
