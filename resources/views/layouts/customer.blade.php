<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kundenportal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        const saved = localStorage.getItem('theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
    </script>
    @vite(['resources/css/fba-design.css'])
    <style>
        .portal-layout{min-height:100dvh;display:flex;flex-direction:column}
        .portal-topbar{background:var(--color-surface);border-bottom:1px solid var(--color-border);padding:0 var(--space-6);height:56px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-sm)}
        .portal-logo{display:flex;align-items:center;gap:var(--space-3);text-decoration:none;color:var(--color-text)}
        .portal-logo svg{color:var(--color-primary)}
        .portal-logo-text{font-size:var(--text-sm);font-weight:600}
        .portal-user{display:flex;align-items:center;gap:var(--space-3);font-size:var(--text-sm);color:var(--color-text-muted)}
        .portal-content{flex:1;padding:var(--space-8);max-width:960px;width:100%;margin:0 auto}
        .portal-nav{display:flex;gap:var(--space-2);margin-bottom:var(--space-6)}
        .portal-nav a{padding:var(--space-2) var(--space-4);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--color-text-muted);text-decoration:none;transition:all var(--transition)}
        .portal-nav a:hover{background:var(--color-surface-dynamic);color:var(--color-text)}
        .portal-nav a.active{background:var(--color-primary-highlight);color:var(--color-primary);font-weight:500}
    </style>
</head>
<body>
<div class="portal-layout">
    <div class="portal-topbar">
        <a href="{{ route('customer.dashboard') }}" class="portal-logo">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <rect width="28" height="28" rx="7" fill="currentColor" opacity=".12"/>
                <path d="M7 10 L14 6 L21 10 L21 18 L14 22 L7 18 Z" stroke="currentColor" stroke-width="1.6" fill="none"/>
                <path d="M7 10 L14 14 L21 10" stroke="currentColor" stroke-width="1.6"/>
                <path d="M14 14 L14 22" stroke="currentColor" stroke-width="1.6"/>
            </svg>
            <span class="portal-logo-text">Kundenportal</span>
        </a>
        <div class="portal-user">
            <span>{{ auth('customer')->user()->name ?? auth('customer')->user()->email }}</span>
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button type="submit" class="icon-btn" title="Abmelden">
                    <i data-lucide="log-out" width="18" height="18"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="portal-content">
        @if(session('success'))
            <div class="alert alert-success">
                <i data-lucide="check-circle" width="18" height="18" class="alert-icon"></i>
                <div><div class="alert-text">{{ session('success') }}</div></div>
            </div>
        @endif

        @yield('content')
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
