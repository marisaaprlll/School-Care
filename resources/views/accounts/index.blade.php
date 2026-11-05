@extends('layouts.app')

@section('title', __('nav.menu_accounts'))

@push('styles')
<style>
    .manual-row{border:1px solid var(--line);border-radius:15px;padding:16px;margin-bottom:14px;background:var(--surface)}
    .manual-row-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;font-size:.8rem}
    .manual-row-head button{border:0;background:transparent;color:var(--danger);font-weight:700;cursor:pointer}
    .manual-row .form-grid{grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
    .manual-row .field{margin-bottom:0}
    .error-list{margin:6px 0 0;padding-left:18px;max-height:260px;overflow:auto}
    .account-create-panel + .account-create-panel{margin-top:24px}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Administrasi akses pengguna</p>
        <h1>Buat akun.</h1>
        <p>Tambah akun satu per satu, atau unggah file untuk membuat ratusan akun sekaligus.</p>
    </div>
</div>

@if ($errors->any())
    <div class="auth-alert auth-alert-error" role="alert">
        <strong>Akun belum dibuat. Tidak ada akun yang disimpan sebelum semua data benar.</strong>
        <ul class="error-list">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ===================== 1. INPUT MANUAL ===================== --}}
<section class="panel form-panel account-create-panel">
    <div class="account-panel-heading">
        <div class="account-panel-icon">+</div>
        <div><h2>Input manual</h2><p>Isi data akun satu per satu. Klik "Tambah akun lagi" kalau ingin membuat beberapa sekaligus.</p></div>
    </div>
    <form method="POST" action="{{ route('accounts.store') }}" id="manualForm">
        @csrf
        <div id="manualRows">
            @foreach ($oldAccounts as $i => $row)
                <div class="manual-row" data-row>
                    <div class="manual-row-head">
                        <strong data-row-title>Akun {{ $i + 1 }}</strong>
                        <button type="button" data-remove-row @if ($i === 0 && count($oldAccounts) === 1) hidden @endif>Hapus</button>
                    </div>
                    <div class="form-grid">
                        <div class="field"><label>Nama lengkap</label>
                            <input type="text" data-f="name" name="accounts[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" maxlength="150" required></div>
                        <div class="field"><label>Email</label>
                            <input type="email" data-f="email" name="accounts[{{ $i }}][email]" value="{{ $row['email'] ?? '' }}" maxlength="150" required></div>
                        <div class="field"><label>Peran</label>
                            <select data-f="role" name="accounts[{{ $i }}][role]" required>
                                @foreach ($roleLabel as $value => $label)
                                    <option value="{{ $value }}" @selected(($row['role'] ?? 'reporter') === $value)>{{ $label }}</option>
                                @endforeach
                            </select></div>
                        <div class="field"><label>Kelas / jabatan</label>
                            <input type="text" data-f="class_name" name="accounts[{{ $i }}][class_name]" value="{{ $row['class_name'] ?? '' }}" maxlength="100" placeholder="XII RPL 1 / Petugas Sarpras" required></div>
                        <div class="field"><label>Password</label>
                            <input type="password" data-f="password" name="accounts[{{ $i }}][password]" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" required></div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="form-footer" style="justify-content:space-between;gap:12px">
            <button class="btn btn-soft" type="button" id="addRow">+ Tambah akun lagi</button>
            <button class="btn btn-primary" type="submit">Simpan akun <span>→</span></button>
        </div>
    </form>
</section>

{{-- ===================== 2. IMPOR DARI FILE ===================== --}}
<section class="panel form-panel account-create-panel">
    <div class="account-panel-heading">
        <div class="account-panel-icon">↑</div>
        <div><h2>Impor akun dari file</h2><p>Unggah Excel, Word, atau CSV untuk membuat banyak akun sekaligus. Akun dibuat otomatis sesuai isi file.</p></div>
    </div>
    <form method="POST" action="{{ route('accounts.store') }}" enctype="multipart/form-data" id="importForm">
        @csrf
        <div class="field">
            <label for="accounts_file">File Excel (.xlsx), Word (.docx), atau CSV</label>
            <input id="accounts_file" name="accounts_file" type="file" accept=".xlsx,.docx,.csv" required>
            <small>
                Baris pertama wajib berisi header: <b>name, email, role, class_name, password</b> (urutan bebas).
                Excel: isi di lembar pertama. Word: isi dalam tabel pertama. Satu baris untuk setiap akun.
                Peran diisi <b>reporter</b> atau <b>admin</b>; password minimal 8 karakter.
                Maksimal 1000 akun dan 5 MB per file. Kalau ada satu baris yang salah, tidak ada akun yang disimpan
                dan kamu akan diberi tahu baris mana yang bermasalah.
            </small>
        </div>
        <div class="form-footer" style="justify-content:space-between;gap:12px">
            <a class="btn btn-soft" href="{{ asset('templates/template-akun.csv') }}" download>Unduh templat CSV</a>
            <button class="btn btn-primary" type="submit" id="importBtn">Unggah file dan buat akun <span>→</span></button>
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

@push('scripts')
<script>
(function () {
    const wrap = document.getElementById('manualRows');
    const addBtn = document.getElementById('addRow');
    const MAX_MANUAL = 50;

    function renumber() {
        const rows = [...wrap.querySelectorAll('[data-row]')];
        rows.forEach((row, i) => {
            row.querySelector('[data-row-title]').textContent = 'Akun ' + (i + 1);
            row.querySelectorAll('[data-f]').forEach((el) => {
                el.name = 'accounts[' + i + '][' + el.dataset.f + ']';
            });
            row.querySelector('[data-remove-row]').hidden = rows.length === 1;
        });
        addBtn.disabled = rows.length >= MAX_MANUAL;
    }

    addBtn.addEventListener('click', () => {
        const rows = wrap.querySelectorAll('[data-row]');
        const clone = rows[rows.length - 1].cloneNode(true);
        clone.querySelectorAll('input').forEach((el) => { el.value = ''; });
        clone.querySelector('select').value = 'reporter';
        wrap.appendChild(clone);
        renumber();
        clone.querySelector('input').focus();
    });

    wrap.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-row]');
        if (!btn) return;
        btn.closest('[data-row]').remove();
        renumber();
    });

    renumber();

    // Impor ratusan akun butuh beberapa detik: cegah klik ganda.
    const importForm = document.getElementById('importForm');
    importForm.addEventListener('submit', () => {
        const btn = document.getElementById('importBtn');
        btn.disabled = true;
        btn.firstChild.textContent = 'Memproses, mohon tunggu... ';
    });
})();
</script>
@endpush
