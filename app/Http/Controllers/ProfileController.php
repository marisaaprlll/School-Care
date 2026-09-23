<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Halaman: Profil (Pelapor & Admin)
 * Tampilan: resources/views/profile/index.blade.php
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.index', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'class_name' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->class_name = $data['class_name'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function accounts(): View
    {
        abort_unless(request()->user()?->isAdmin(), 403, 'Hanya admin yang dapat membuat akun.');

        return view('accounts.index', [
            'users' => User::whereKeyNot(request()->user()->id)
                ->orderBy('role')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function createUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat menambah akun.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', 'in:reporter,admin'],
            'class_name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'name' => 'nama',
            'email' => 'email',
            'role' => 'peran',
            'class_name' => 'kelas / jabatan',
            'password' => 'password',
        ]);

        $initials = preg_replace('/[^A-Za-z]/', '', $data['name']);
        $avatar = strtoupper(substr($initials, 0, 2) ?: 'US');

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => User::roleFromEmail($data['email']),
            'class_name' => $data['class_name'],
            'avatar' => $avatar,
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('accounts.index')->with('success', 'Akun baru berhasil dibuat.');
    }
}
