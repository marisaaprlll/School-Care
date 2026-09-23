<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            'Lab Komputer 1',
            'Lab Komputer 2',
            'Ruang Kelas 2.04',
            'Ruang Kelas 3.01',
            'Perpustakaan',
            'Toilet Gedung B',
            'Lapangan Indoor',
        ];
        foreach ($locations as $name) {
            Location::create(['name' => $name]);
        }

        $categories = [
            'Elektronik',
            'Furnitur',
            'Bangunan',
            'Kebersihan',
            'Jaringan / Internet',
        ];
        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}
