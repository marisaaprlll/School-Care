<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan seeding: user dulu (karena report butuh reporter_id),
     * lalu data master, baru data transaksi laporan.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LocationCategorySeeder::class,
            ReportSeeder::class,
        ]);
    }
}
