# School Care — Versi Laravel

Sistem Pengaduan Sarana Sekolah, dikonversi dari `school-care-publishable.html`
(satu file HTML + JS) menjadi struktur **Laravel 11** yang rapi: satu halaman
= satu file, teks Bahasa Indonesia dipisah dari kode.

---

## 🗺️ Peta halaman — cari kode suatu halaman di sini

| Halaman (menu di sidebar) | URL | Controller | View (tampilan) | Teks Bahasa Indonesia |
|---|---|---|---|---|
| Login | `/` | `app/Http/Controllers/Auth/LoginController.php` | `resources/views/auth/login.blade.php` | `resources/lang/id/auth.php` |
| **Ringkasan** | `/dashboard` | `app/Http/Controllers/DashboardController.php` | `resources/views/dashboard/index.blade.php` | `resources/lang/id/dashboard.php` |
| **Laporan Saya** (Pelapor) | `/laporan-saya` | `app/Http/Controllers/ReportController.php@index` | `resources/views/reports/index.blade.php` | `resources/lang/id/reports.php` |
| **Antrean Laporan** (Admin) | `/antrean-laporan` | `app/Http/Controllers/ReportController.php@queue` | `resources/views/reports/queue.blade.php` | `resources/lang/id/reports.php` |
| **Ajukan Laporan** | `/laporan/ajukan` | `app/Http/Controllers/ReportController.php@create,store` | `resources/views/reports/create.blade.php` | `resources/lang/id/form.php` |
| Detail laporan | `/laporan/{id}` | `app/Http/Controllers/ReportController.php@show,updateStatus` | `resources/views/reports/show.blade.php` | — |
| **Data Master** (Admin) | `/data-master` | `app/Http/Controllers/MasterDataController.php` | `resources/views/master/index.blade.php` | `resources/lang/id/master.php` |
| **Profil** | `/profil` | `app/Http/Controllers/ProfileController.php` | `resources/views/profile/index.blade.php` | `resources/lang/id/profile.php` |

Semua rute juga didaftarkan dengan komentar penjelas di **`routes/web.php`** —
buka file itu kalau mau tahu daftar lengkap URL dalam satu layar.

---

## 📁 Struktur folder penting

```
school-care/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Logika tiap halaman (lihat tabel di atas)
│   │   │   └── Auth/LoginController.php
│   │   └── Middleware/
│   │       └── EnsureUserIsAdmin.php   # Kunci halaman khusus Admin
│   └── Models/                   # User, Report, Location, Category
│
├── database/
│   ├── migrations/               # Struktur tabel (users, reports, locations, categories)
│   └── seeders/                  # Data contoh (2 akun demo + 7 laporan)
│
├── resources/
│   ├── views/
│   │   ├── layouts/              # Kerangka halaman (app.blade.php = sidebar+topbar)
│   │   ├── components/           # Sidebar, topbar, kartu statistik, kartu laporan
│   │   ├── auth/login.blade.php
│   │   ├── dashboard/index.blade.php
│   │   ├── reports/               # index, queue, create, show
│   │   ├── master/index.blade.php
│   │   └── profile/index.blade.php
│   └── lang/id/                  # ⭐ SEMUA teks Bahasa Indonesia, dipisah per halaman
│       ├── common.php            # Tombol & label umum (Simpan, Hapus, status, dst.)
│       ├── nav.php                # Menu sidebar & topbar
│       ├── auth.php               # Halaman login
│       ├── dashboard.php          # Halaman Ringkasan
│       ├── reports.php            # Laporan Saya & Antrean Laporan
│       ├── form.php               # Ajukan Laporan
│       ├── master.php             # Data Master
│       └── profile.php            # Profil
│
├── public/
│   ├── css/app.css               # Semua styling asli, ditata ulang per bagian (baca komentarnya)
│   └── js/app.js                 # HANYA interaksi tampilan (toggle password, menu mobile, dst.)
│
├── routes/web.php                # Peta URL → Controller, dengan komentar per bagian
├── composer.json
└── .env.example
```

### Kenapa teksnya dipisah ke `resources/lang/id/*.php`?

Supaya kamu (atau guru penguji) bisa mengganti kalimat di aplikasi **tanpa
menyentuh kode PHP/Blade sama sekali** — cukup buka file bahasa yang sesuai.
Ini juga pola resmi Laravel untuk lokalisasi, jadi kalau suatu saat perlu versi
Bahasa Inggris, tinggal buat folder `resources/lang/en/` dengan struktur yang sama.

Contoh pemakaian di Blade: `{{ __('dashboard.title_reporter') }}`
→ Laravel otomatis mengambil dari `resources/lang/id/dashboard.php`.

---

## 🔁 Apa yang berubah dari versi HTML lama?

| Versi lama (HTML + JS) | Versi Laravel (sekarang) |
|---|---|
| Semua HTML digabung dalam satu file `<script>` (template string JS) | Tiap halaman jadi file `.blade.php` sendiri |
| Data disimpan di `localStorage` browser | Data disimpan di database MySQL `school_care` lewat Model & Migration |
| Routing manual (`activePage`, `renderCurrent()`) | Routing asli Laravel di `routes/web.php` |
| Login palsu (cek array `seedUsers` di JS) | Login sungguhan pakai `Auth::attempt()` + password terenkripsi |
| Semua teks tertulis langsung di template JS | Teks dipisah ke `resources/lang/id/*.php` |
| Modal detail laporan (dialog) | Halaman tersendiri `/laporan/{id}` (lebih mudah di-bookmark & di-share) |

---

## ⚙️ Cara menjalankan

Paket ini berisi **kode aplikasi School Care** (Controller, Model, View,
Migration, Seeder, routes, lang, CSS/JS). Folder inti Laravel (`vendor/`,
`config/`, `artisan`, `public/index.php`, dst.) belum disertakan karena
ukurannya besar dan sebaiknya diambil langsung dari installer resmi Laravel
supaya versinya selalu terbaru.

**Langkah setup:**

```bash
# 1. Buat proyek Laravel kosong (ini akan mengisi vendor/, config/, artisan, dll.)
composer create-project laravel/laravel school-care-app
cd school-care-app

# 2. Salin/timpa folder dari paket ini ke dalam proyek barumu:
#    app/, database/, resources/, routes/, public/css, public/js, composer.json, .env.example

# 3. Install dependency & siapkan .env
composer install
cp .env.example .env
php artisan key:generate

# 4. Buat database MySQL `school_care`, lalu jalankan migrasi dan seeder
php artisan migrate --seed

# Alternatif: impor struktur dari school_care.sql, lalu buat data contoh
# dengan menjalankan: php artisan db:seed
# Dump menyertakan riwayat migrasi, tetapi tidak berisi baris akun/laporan/sesi.
# Import lewat phpMyAdmin atau jalankan: mysql -u root school_care < school_care.sql

# 5. (Opsional) Buat tautan untuk berkas di disk publik
php artisan storage:link

# 6. Jalankan
php artisan serve
```

Foto bukti laporan ditampilkan melalui rute aplikasi yang memeriksa hak akses, sehingga tidak bergantung pada tautan `public/storage`.

Buka `http://localhost:8000`, lalu login pakai salah satu akun demo:

| Peran | Email | Password |
|---|---|---|
| Admin / Petugas Sarpras | `admin@schoolcare.id` | `SchoolCare123!` |

---

## 🧩 Kalau mau menambah halaman baru

1. Buat method baru di controller yang sesuai (atau controller baru di `app/Http/Controllers/`).
2. Buat file Blade baru di `resources/views/`.
3. Daftarkan URL-nya di `routes/web.php`.
4. Kalau ada teks baru, tambahkan key baru di file `resources/lang/id/*.php` yang relevan
   (atau buat file bahasa baru kalau halamannya benar-benar baru), lalu panggil dengan `__('nama_file.key')`.
5. Tambahkan menu ke sidebar di `resources/views/components/sidebar.blade.php`.


## Perubahan akses akun

- Halaman utama hanya menampilkan login. Registrasi publik dinonaktifkan.
- Admin memiliki menu **Buat Akun** terpisah untuk membuat akun Pelapor atau Admin lain.
- Admin dapat mengimpor CSV, Excel modern (`.xlsx`), atau Word (`.docx`) dari halaman **Buat Akun** (maksimal 1000 akun per file; seluruh data divalidasi dulu, kalau ada satu baris salah tidak ada akun yang disimpan dan nomor baris yang bermasalah ditampilkan). Halaman yang sama juga menyediakan input manual, bisa lebih dari satu akun sekaligus. Ekstensi PHP ZIP (`extension=zip`) harus aktif untuk membaca file Excel dan Word.
- Excel dibaca dari lembar pertama, sedangkan Word dibaca dari tabel pertama. Baris pertama wajib berisi header `name`, `email`, `role`, `class_name`, dan `password`; setiap baris berikutnya berisi satu akun. Peran diisi `reporter` atau `admin`, dan password minimal 8 karakter.
- Unduh `public/templates/template-akun.csv` dari halaman **Buat Akun** untuk contoh format impor.
- Profil hanya berisi pengaturan profil dan logout.
- Daftar akun yang dibuat Admin tampil di halaman **Buat Akun** dan tersimpan di database.
