<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // 'reporter' = Siswa/Guru pelapor, 'admin' = Petugas Sarpras
            $table->enum('role', ['reporter', 'admin'])->default('reporter');
            $table->string('class_name')->nullable(); // Kelas (pelapor) / Jabatan (admin)
            $table->string('avatar', 4)->nullable();  // Inisial 2 huruf, mis. "MA"
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
