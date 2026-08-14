<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — FBA Umlagerung')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        const saved = localStorage.getItem('theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
    </script>
    @vite(['resources/css/fba-design.css'])
</head>
<body>
<div class="app">
    <header class="topbar">
        <a class="topbar-logo" href="{{ route('admin.dashboard') }}">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-label="Admin Logo">
                <rect width="28" height="28" rx="7" fill="currentColor" opacity=".12"/>
                <path d="M7 10 L14 6 L21 10 L21 18 L14 22 L7 18 Z" stroke="currentColor" stroke-width="1.6" fill="none"/>
                <path d="M7 10 L14 14 L21 10" stroke="currentColor" stroke-width="1.6"/>
                <path d="M14 14 L14 22" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            <span class="topbar-logo-text">Admin Panel</span>
            <span class="topbar-logo-badge" style="background:var(--color-error);color:#fff">SUPERADMIN</span>
        </a>
        <div class="topbar-right">
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary" style="margin-right:var(--space-2)">
                <i data-lucide="arrow-left" width="14" height="14"></i> Zum Dashboard
            </a>
            <button class="icon-btn" onclick="toggleTheme()" aria-label="Theme wechseln">
                <i data-lucide="sun" width="18" height="18"></i>
            </button>
            @auth
            <form method="POST" action="{{ route('logout') }}" style="display:flex;align-items:center;">
                @csrf
                <button type="submit" class="icon-btn" aria-label="Logout">
                    <i data-lucide="log-out" width="18" height="18"></i>
                </button>
            </form>
            @endauth
        </div>
    </header>

    <div class="main">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-section-label">Administration</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" width="16" height="16"></i> Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i data-lucide="users" width="16" height="16"></i> Benutzer
                </a>
            </div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-section">
                <div class="sidebar-section-label">System</div>
                <a href="{{ route('admin.system') }}" class="nav-item {{ request()->routeIs('admin.system') ? 'active' : '' }}">
                    <i data-lucide="server" width="16" height="16"></i> Systeminfo
                </a>
            </div>
        </aside>

        <main class="content">
            @if(session('success'))
                <div class="alert alert-success">
                    <i data-lucide="check-circle" width="18" height="18" class="alert-icon"></i>
                    <div><div class="alert-title">Erfolg</div><div class="alert-text">{{ session('success') }}</div></div>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    <i data-lucide="alert-triangle" width="18" height="18" class="alert-icon"></i>
                    <div><div class="alert-title">Fehler</div><div class="alert-text">{{ session('error') }}</div></div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
function toggleTheme() {
    const h = document.documentElement;
    const cur = h.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    h.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    lucide.createIcons();
}
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@yield('scripts')
</body>
</html>
