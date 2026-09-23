<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun admin awal untuk mengelola aplikasi.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Sarpras',
            'email' => 'admin@schoolcare.id',
            'password' => Hash::make('SchoolCare123!'),
            'role' => 'admin',
            'class_name' => 'Petugas Sarpras',
            'avatar' => 'AS',
        ]);
    }
}
