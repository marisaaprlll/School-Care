<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ __('common.app_name') }} | @yield('title', __('nav.menu_dashboard'))</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
@stack('styles')
</head>
<body>
<div class="app-shell" id="appShell">

    {{-- Menu & profil ringkas — isinya di resources/views/components/sidebar.blade.php --}}
    <x-sidebar :active="$activePage ?? 'dashboard'" />

    <div class="mobile-overlay" id="mobileOverlay"></div>

    <main class="main">
        {{-- Breadcrumb, search, ikon notifikasi — resources/views/components/topbar.blade.php --}}
        <x-topbar :crumb="$crumb ?? __('nav.menu_dashboard')" />

        <div class="page">
            <section class="page-screen active">
                @if (session('success'))
                    <div class="success" style="margin-bottom:16px">{{ session('success') }}</div>
                @endif

                {{-- Konten tiap halaman ditulis di sini lewat @yield('content') --}}
                @yield('content')
            </section>
        </div>
    </main>
</div>

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
