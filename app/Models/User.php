<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model — merepresentasikan akun Pelapor (siswa/guru) & Admin (petugas Sarpras).
 *
 * Kolom penting:
 * - role      : 'reporter' | 'admin'
 * - class_name: kelas untuk pelapor, atau jabatan untuk admin (mis. "Petugas Sarpras")
 * - avatar    : inisial 2 huruf yang ditampilkan di sidebar/topbar
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'class_name',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public static function roleFromEmail(string $email): string
    {
        $normalizedEmail = strtolower(trim($email));

        if (str_ends_with($normalizedEmail, '@schoolcare.id')) {
            return 'admin';
        }

        return 'reporter';
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }
}
