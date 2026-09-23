<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Contoh: SC-0241
            $table->string('title');
            $table->string('location');
            $table->string('category');
            $table->date('incident_date');
            $table->enum('status', ['Diajukan', 'Diproses', 'Selesai', 'Ditolak'])->default('Diajukan');
            $table->string('priority')->default('Normal');
            $table->text('description');
            $table->text('note')->nullable(); // catatan tindak lanjut dari admin
            $table->string('evidence_path')->nullable(); // path foto bukti di storage
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
