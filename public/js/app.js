/**
 * public/js/app.js
 * ------------------------------------------------------------------
 * Catatan penting: di versi HTML lama, SEMUA logika (login, simpan
 * laporan, ubah status, dsb) ada di file JS ini lewat localStorage.
 *
 * Sekarang logika itu sudah pindah ke server Laravel:
 *   - Login          -> app/Http/Controllers/Auth/LoginController.php
 *   - Simpan laporan -> app/Http/Controllers/ReportController.php
 *   - Ubah status     -> app/Http/Controllers/ReportController.php
 *   - Data master     -> app/Http/Controllers/MasterDataController.php
 *
 * File ini HANYA berisi interaksi tampilan (UI) yang tidak perlu ke
 * server: toggle password, buka/tutup menu mobile, isi form akun
 * demo, dan pratinjau nama file upload.
 * ------------------------------------------------------------------
 */
(function () {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

    /** Tampilkan/sembunyikan isi input password di halaman login. */
    function initPasswordToggle() {
        $$('[data-toggle-password]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.togglePassword);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.textContent = isPassword ? 'Sembunyikan' : 'Tampilkan';
            });
        });
    }

    /** Tombol "Pakai akun" demo -> otomatis isi email & password. */
    function initDemoLogin() {
        $$('[data-demo-email]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const emailInput = document.getElementById('loginEmail');
                const passwordInput = document.getElementById('loginPassword');
                if (emailInput) emailInput.value = btn.dataset.demoEmail;
                if (passwordInput) passwordInput.value = 'SchoolCare123!';
            });
        });
    }

    /** Sidebar mobile: buka/tutup lewat tombol hamburger + overlay. */
    function initMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        const menuBtn = document.getElementById('mobileMenu');
        if (!sidebar || !menuBtn) return;

        const closeMenu = (restoreFocus = false) => {
            sidebar.classList.remove('open');
            overlay?.classList.remove('open');
            document.body.classList.remove('mobile-menu-open');
            menuBtn.setAttribute('aria-expanded', 'false');
            menuBtn.setAttribute('aria-label', 'Buka menu');
            if (restoreFocus) menuBtn.focus();
        };
        const openMenu = () => {
            sidebar.classList.add('open');
            overlay?.classList.add('open');
            document.body.classList.add('mobile-menu-open');
            menuBtn.setAttribute('aria-expanded', 'true');
            menuBtn.setAttribute('aria-label', 'Tutup menu');
        };

        menuBtn.addEventListener('click', () => {
            sidebar.classList.contains('open') ? closeMenu(true) : openMenu();
        });
        overlay?.addEventListener('click', () => closeMenu(true));
        sidebar.querySelectorAll('.nav a').forEach((link) => {
            link.addEventListener('click', () => closeMenu());
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) {
                closeMenu(true);
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 760 && sidebar.classList.contains('open')) {
                closeMenu();
            }
        });
    }

    /** Form Ajukan Laporan: tampilkan nama file yang dipilih. */
    function initEvidencePreview() {
        const input = document.getElementById('evidence');
        const label = document.getElementById('fileName');
        if (!input || !label) return;

        input.addEventListener('change', () => {
            label.textContent = input.files?.[0]?.name || '';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initPasswordToggle();
        initDemoLogin();
        initMobileSidebar();
        initEvidencePreview();
    });

    // Diekspos supaya bisa dipanggil ulang dari @push('scripts') di halaman tertentu.
    window.SchoolCare = { initEvidencePreview, initMobileSidebar };
})();
