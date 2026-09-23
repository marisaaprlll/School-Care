<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Data master lokasi/ruang (dikelola Admin di halaman Data Master,
 * dipakai sebagai pilihan dropdown di form Ajukan Laporan).
 */
class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
