@extends('layouts.app')

@php($activePage = 'accounts')
@php($crumb = __('nav.menu_accounts'))

@section('title', __('nav.menu_accounts'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Administrasi akses pengguna</p>
        <h1>Buat akun.</h1>
        <p>Buat akun Admin Sarpras atau Pelapor dari halaman khusus ini. Halaman ini hanya bisa dibuka oleh Admin.</p>
    </div>
</div>

<div class="account-layout">
    <section class="panel form-panel account-create-panel">
        <div class="account-panel-heading">
            <div class="account-panel-icon">＋</div>
            <div><h2>Buat akun baru</h2><p>Data akun langsung tersimpan ke database.</p></div>
        </div>
        <form method="POST" action="{{ route('accounts.store') }}">
            @csrf
            @if ($errors->any())
                <div class="auth-alert auth-alert-error" role="alert"><strong>Data belum lengkap.</strong><span>{{ $errors->first() }}</span></div>
            @endif
            <div class="form-grid">
                <div class="field"><label for="newUserName">Nama lengkap</label><input id="newUserName" name="name" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" required></div>
                <div class="field"><label for="newUserEmail">Email</label><input id="newUserEmail" name="email" type="email" value="{{ old('email') }}" placeholder="nama@schoolcare.id" required></div>
                <div class="field"><label for="newUserRole">Peran akun</label><select id="newUserRole" name="role" required><option value="reporter" {{ old('role','reporter') === 'reporter' ? 'selected' : '' }}>Pelapor</option><option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin / Petugas Sarpras</option></select></div>
                <div class="field"><label for="newUserClass">Kelas / jabatan</label><input id="newUserClass" name="class_name" value="{{ old('class_name') }}" placeholder="Contoh: XII RPL B" required></div>
                <div class="field"><label for="newUserPassword">Password sementara</label><input id="newUserPassword" name="password" type="password" placeholder="Minimal 8 karakter" required></div>
                <div class="field"><label for="newUserPasswordConfirm">Konfirmasi password</label><input id="newUserPasswordConfirm" name="password_confirmation" type="password" placeholder="Ulangi password" required></div>
            </div>
            <div class="form-footer"><button class="btn btn-primary" type="submit">Simpan akun <span>→</span></button></div>
        </form>
    </section>

    <aside class="panel account-help-panel">
        <p class="eyebrow">Kontrol akses</p>
        <h2>Bagikan akun dengan aman.</h2>
        <p>Admin menentukan peran setiap pengguna. Pelapor bisa mengajukan laporan, sedangkan Admin bisa memverifikasi dan mengelola data.</p>
        <div class="role-note"><span class="pill pill-blue">Pelapor</span><span>Mengajukan laporan dan memantau status.</span></div>
        <div class="role-note"><span class="pill pill-coral">Admin</span><span>Mengelola laporan, master data, dan akun.</span></div>
    </aside>
</div>

<section class="panel accounts-list-panel">
    <div class="panel-head"><div><h2>Daftar akun</h2><p>{{ $users->count() }} akun terdaftar di database.</p></div><span class="pill pill-mint">Admin only</span></div>
    <div class="table-wrap">
        <table class="accounts-table">
            <thead><tr><th>Pengguna</th><th>Email</th><th>Peran</th><th>Kelas / jabatan</th><th>Dibuat</th></tr></thead>
            <tbody>
                @foreach ($users as $account)
                    <tr><td><div class="report-title"><span class="account-avatar">{{ $account->avatar }}</span><strong>{{ $account->name }}</strong></div></td><td>{{ $account->email }}</td><td><span class="pill {{ $account->isAdmin() ? 'pill-coral' : 'pill-blue' }}">{{ $account->isAdmin() ? 'Admin' : 'Pelapor' }}</span></td><td>{{ $account->class_name }}</td><td>{{ $account->created_at?->translatedFormat('d M Y') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
