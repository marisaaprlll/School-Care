<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Data master kategori kerusakan (Elektronik, Furnitur, Bangunan, dst).
 * Dikelola Admin di halaman Data Master.
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
