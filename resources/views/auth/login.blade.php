@extends('layouts.guest')

@section('content')
<div class="auth-screen auth-screen-v3">
    <section class="auth-visual">
        <div class="brand">
            <div class="brand-mark">♥</div>
            <div>
                <span class="brand-name">{{ __('common.app_name') }}</span>
                <span class="brand-sub">{{ __('common.app_tagline') }}</span>
            </div>
        </div>
        <div class="auth-copy">
            <p class="eyebrow">Sistem Pengaduan Sarana Sekolah</p>
            <h1>Masalah fasilitas, <em>jangan berhenti</em> jadi keluhan.</h1>
            <p>Ajukan laporan kerusakan dengan bukti yang jelas, lalu pantau penanganannya sampai selesai.</p>
        </div>
        <div class="auth-points">
            <div class="auth-point"><strong>01 · Catat</strong><span>Semua laporan tersimpan rapi.</span></div>
            <div class="auth-point"><strong>02 · Pantau</strong><span>Status transparan untuk pelapor.</span></div>
            <div class="auth-point"><strong>03 · Tindak</strong><span>Petugas punya antrean terukur.</span></div>
        </div>
    </section>

    <section class="auth-panel auth-panel-v3">
        <div class="login-card login-card-v3">
            <div class="login-kicker"><span class="login-kicker-dot"></span><span>Akses resmi School Care</span></div>
            <p class="eyebrow">Selamat datang kembali</p>
            <h2>Masuk ke School Care.</h2>
            <p class="form-lead">Gunakan akun yang dibuat oleh Admin Sarpras untuk melanjutkan.</p>

            @if ($errors->any())
                <div class="auth-alert auth-alert-error" role="alert"><strong>Belum bisa masuk.</strong><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div class="field">
                    <label for="loginEmail">Email</label>
                    <input id="loginEmail" name="email" type="email" autocomplete="username" placeholder="nama@schoolcare.id" value="" required autofocus>
                </div>
                <div class="field">
                    <label for="loginPassword">Password</label>
                    <div class="password-wrap">
                        <input id="loginPassword" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required>
                        <button type="button" class="show-pass" data-toggle-password="loginPassword">Tampilkan</button>
                    </div>
                </div>
                <div class="login-options">
                    <label class="checkline"><input name="remember" type="checkbox" value="1" checked> Ingat saya</label>
                    <span class="muted">School Care v1.0</span>
                </div>
                <button class="btn btn-primary btn-full auth-submit" type="submit">Masuk ke dashboard <span>→</span></button>
            </form>

            <p class="login-footer-note">Belum punya akun? Hubungi Admin Sarpras untuk dibuatkan akun.</p>
        </div>
    </section>
</div>
@endsection
