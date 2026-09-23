<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Data contoh identik dengan seed data pada versi HTML statis,
     * supaya tampilan dashboard/daftar laporan langsung "berisi" saat pertama dicoba.
     */
    public function run(): void
    {
        $reporterId = User::where('email', 'siswa@schoolcare.id')->value('id')
            ?? User::where('role', 'admin')->value('id');

        $reports = [
            ['code' => 'SC-0241', 'title' => 'Kipas ruang 2.04 tidak berputar', 'location' => 'Ruang Kelas 2.04', 'category' => 'Elektronik', 'incident_date' => '2026-09-08', 'status' => 'Diproses', 'priority' => 'Normal', 'description' => 'Kipas bagian depan tidak berputar sejak jam pelajaran kedua. Ruangan terasa panas dan mengganggu konsentrasi.', 'note' => 'Teknisi sedang mengecek sambungan listrik dan kapasitor.'],
            ['code' => 'SC-0235', 'title' => 'Lampu Lab Komputer 1 mati', 'location' => 'Lab Komputer 1', 'category' => 'Elektronik', 'incident_date' => '2026-09-05', 'status' => 'Diajukan', 'priority' => 'Normal', 'description' => 'Dua lampu di sisi belakang lab tidak menyala.', 'note' => 'Menunggu verifikasi petugas.'],
            ['code' => 'SC-0219', 'title' => 'Keran wastafel bocor', 'location' => 'Toilet Gedung B', 'category' => 'Bangunan', 'incident_date' => '2026-08-28', 'status' => 'Selesai', 'priority' => 'Normal', 'description' => 'Keran wastafel sebelah kanan bocor dan membuat area lantai licin.', 'note' => 'Keran sudah diganti pada 30 Agu 2026.'],
            ['code' => 'SC-0246', 'title' => 'Proyektor ruang 3.01 tidak menyala', 'location' => 'Ruang Kelas 3.01', 'category' => 'Elektronik', 'incident_date' => '2026-09-08', 'status' => 'Diajukan', 'priority' => 'Urgent', 'description' => 'Proyektor tidak menampilkan gambar saat akan digunakan untuk presentasi.', 'note' => 'Perlu cek kabel HDMI dan sumber listrik.'],
            ['code' => 'SC-0238', 'title' => 'Pintu lemari lab sulit dikunci', 'location' => 'Lab Komputer 2', 'category' => 'Furnitur', 'incident_date' => '2026-09-07', 'status' => 'Diproses', 'priority' => 'Normal', 'description' => 'Kunci lemari server tersendat dan pintu tidak rapat.', 'note' => 'Petugas sudah mengukur penggantian engsel.'],
            ['code' => 'SC-0232', 'title' => 'Saluran air lapangan indoor mampet', 'location' => 'Lapangan Indoor', 'category' => 'Kebersihan', 'incident_date' => '2026-09-04', 'status' => 'Selesai', 'priority' => 'Urgent', 'description' => 'Air menggenang di sudut lapangan setelah hujan.', 'note' => 'Saluran sudah dibersihkan.'],
            ['code' => 'SC-0227', 'title' => 'Kabel jaringan terkelupas', 'location' => 'Lab Komputer 1', 'category' => 'Jaringan / Internet', 'incident_date' => '2026-09-02', 'status' => 'Ditolak', 'priority' => 'Normal', 'description' => 'Pelapor mengirim foto yang tidak menunjukkan lokasi. Mohon ajukan ulang dengan bukti lebih jelas.', 'note' => 'Ditolak karena bukti belum bisa diverifikasi.'],
        ];

        foreach ($reports as $r) {
            Report::create($r + ['reporter_id' => $reporterId]);
        }
    }
}
