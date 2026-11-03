<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_form_posts_to_the_login_endpoint(): void
    {
        $response = $this->get('/');

        $response->assertSee('method="POST"', false);
        $response->assertSee('action="http://localhost:8000/login"', false);
    }

    public function test_report_search_filters_matching_reports_for_reporter(): void
    {
        $user = User::factory()->create([
            'role' => 'reporter',
            'class_name' => 'XII RPL B',
        ]);

        Report::create([
            'code' => 'SC-0001',
            'title' => 'Lampu lab mati',
            'location' => 'Lab Komputer 1',
            'category' => 'Elektronik',
            'incident_date' => '2026-09-10',
            'status' => 'Diajukan',
            'priority' => 'Normal',
            'description' => 'Lampu utama mati di lab komputer',
            'evidence_path' => 'reports/demo.jpg',
            'reporter_id' => $user->id,
        ]);

        Report::create([
            'code' => 'SC-0002',
            'title' => 'Pintu kelas macet',
            'location' => 'Ruang Kelas 3.01',
            'category' => 'Furnitur',
            'incident_date' => '2026-09-11',
            'status' => 'Diproses',
            'priority' => 'Normal',
            'description' => 'Pintu kelas sulit ditutup',
            'evidence_path' => 'reports/demo2.jpg',
            'reporter_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/laporan-saya?q=lampu')
            ->assertStatus(200)
            ->assertSee('Lampu lab mati')
            ->assertDontSee('Pintu kelas macet');
    }

    public function test_profile_update_persists_new_values_to_database(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'class_name' => 'XI TKJ A',
            'role' => 'reporter',
            'password' => 'OldSecret123!',
        ]);

        $this->actingAs($user)
            ->put('/profil', [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'class_name' => 'XII RPL B',
                'password' => 'Secret123!',
                'password_confirmation' => 'Secret123!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'class_name' => 'XII RPL B',
            'avatar' => 'NE',
        ]);

        $updatedUser = User::findOrFail($user->id);
        $this->assertTrue(Hash::check('Secret123!', $updatedUser->password));
        $this->assertFalse(Hash::check('OldSecret123!', $updatedUser->password));
    }

    public function test_completed_report_is_verified_visible_to_reporter_and_cannot_change_again(): void
    {
        $reporter = User::factory()->create(['role' => 'reporter']);
        $admin = User::factory()->create(['role' => 'admin']);
        $report = Report::create([
            'code' => 'SC-0003',
            'title' => 'Kursi rusak',
            'location' => 'Ruang Kelas 1.01',
            'category' => 'Furnitur',
            'incident_date' => '2026-09-12',
            'status' => 'Diproses',
            'priority' => 'Normal',
            'description' => 'Kaki kursi patah.',
            'reporter_id' => $reporter->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('reports.updateStatus', $report), [
                'status' => 'Selesai',
                'note' => 'Perbaikan selesai dan sudah diverifikasi.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'Selesai',
            'note' => 'Perbaikan selesai dan sudah diverifikasi.',
        ]);

        $this->actingAs($reporter)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('Perbaikan selesai dan sudah diverifikasi.');

        $this->actingAs($admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.updateStatus', $report), [
                'status' => 'Diproses',
                'note' => 'Coba ubah lagi.',
            ])
            ->assertRedirect(route('reports.show', $report))
            ->assertSessionHas('error');

        $this->get(route('reports.show', $report))
            ->assertSee('statusnya tidak dapat diubah lagi.');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'Selesai',
            'note' => 'Perbaikan selesai dan sudah diverifikasi.',
        ]);
    }

    public function test_admin_can_create_new_user_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'class_name' => 'Petugas Sarpras',
        ]);

        $this->actingAs($admin)
            ->post('/buat-akun', [
                'name' => 'User Baru',
                'email' => 'userbaru@example.com',
                'role' => 'reporter',
                'class_name' => 'XII TBSM A',
                'password' => 'Secret123!',
                'password_confirmation' => 'Secret123!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'userbaru@example.com',
            'role' => 'reporter',
            'class_name' => 'XII TBSM A',
        ]);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->post('/register', [
            'name' => 'Akun Baru',
            'email' => 'akunbaru@example.com',
            'class_name' => 'XII RPL A',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ])->assertNotFound();
    }

}
