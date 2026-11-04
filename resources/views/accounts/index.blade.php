@extends('layouts.app')

@php($activePage = 'accounts')
@php($crumb = __('nav.menu_accounts'))

@section('title', __('nav.menu_accounts'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Administrasi akses pengguna</p>
        <h1>Buat akun.</h1>
        <p>Unggah file berisi data akun untuk membuat semuanya sekaligus.</p>
    </div>
</div>

@if (session('success'))
    <div class="auth-alert" role="status">{{ session('success') }}</div>
@endif

<section class="panel form-panel account-create-panel">
    <div class="account-panel-heading">
        <div class="account-panel-icon">↑</div>
        <div><h2>Impor akun dari file</h2><p>Unggah Excel, Word, atau CSV untuk membuat banyak akun sekaligus.</p></div>
    </div>
    <form method="POST" action="{{ route('accounts.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($errors->any())
            <div class="auth-alert auth-alert-error" role="alert">
                <strong>Akun belum dibuat.</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
        <div class="field">
            <label for="accounts_file">File Excel (.xlsx), Word (.docx), atau CSV</label>
            <input id="accounts_file" name="accounts_file" type="file" accept=".xlsx,.docx,.csv" required>
            <small>
                Baris header wajib berisi: name, email, role, class_name, password.
                Excel: isi di lembar pertama. Word: isi dalam tabel. Satu baris untuk setiap akun.
                Peran diisi reporter atau admin; password minimal 8 karakter. Maksimal 100 akun per file.
            </small>
        </div>
        <div class="form-footer" style="justify-content:space-between;gap:12px">
            <a class="btn btn-soft" href="{{ asset('templates/template-akun.csv') }}" download>Unduh templat CSV</a>
            <button class="btn btn-primary" type="submit">Unggah file dan buat akun <span>→</span></button>
        </div>
    </form>
</section>

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
