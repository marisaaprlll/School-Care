{{--
    Komponen Sidebar. Dipakai di layouts/app.blade.php lewat <x-sidebar :active="..." />
    $active menentukan menu mana yang disorot (dashboard | reports | new-report | master | profile).
--}}
@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();

    $navItems = $isAdmin
        ? [
            ['key' => 'dashboard', 'icon' => '▦', 'label' => __('nav.menu_dashboard'), 'route' => route('dashboard')],
            ['key' => 'reports', 'icon' => '▤', 'label' => __('nav.menu_reports_admin'), 'route' => route('reports.queue')],
            ['key' => 'master', 'icon' => '⚙', 'label' => __('nav.menu_master'), 'route' => route('master.index')],
            ['key' => 'accounts', 'icon' => '＋', 'label' => __('nav.menu_accounts'), 'route' => route('accounts.index')],
            ['key' => 'profile', 'icon' => '◉', 'label' => __('nav.menu_profile'), 'route' => route('profile.edit')],
        ]
        : [
            ['key' => 'dashboard', 'icon' => '▦', 'label' => __('nav.menu_dashboard'), 'route' => route('dashboard')],
            ['key' => 'reports', 'icon' => '▤', 'label' => __('nav.menu_reports_reporter'), 'route' => route('reports.index')],
            ['key' => 'new-report', 'icon' => '＋', 'label' => __('nav.menu_new_report'), 'route' => route('reports.create')],
            ['key' => 'profile', 'icon' => '◉', 'label' => __('nav.menu_profile'), 'route' => route('profile.edit')],
        ];
@endphp
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark">♥</div>
        <div>
            <span class="brand-name">{{ __('common.app_name') }}</span>
            <span class="brand-sub">{{ __('common.app_tagline') }}</span>
        </div>
    </div>

    <p class="eyebrow side">{{ $isAdmin ? __('nav.workspace_admin') : __('nav.workspace_reporter') }}</p>

    <nav class="nav">
        @foreach ($navItems as $item)
            <a href="{{ $item['route'] }}" class="nav-btn {{ $active === $item['key'] ? 'active' : '' }}">
                <span class="nav-emoji">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="sidebar-spacer"></div>

    <div class="user-chip">
        <div class="avatar">{{ $user->avatar }}</div>
        <div>
            <strong>{{ $user->name }}</strong>
            <small>{{ $isAdmin ? __('profile.role_admin') : __('profile.role_reporter_prefix') . $user->class_name }}</small>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout-mini" type="submit" title="{{ __('nav.logout') }}">↪</button>
        </form>
    </div>
</aside>
