<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Support')</title>
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
        .public-layout{min-height:100dvh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:var(--space-8);background:var(--color-bg)}
        .public-card{width:100%;max-width:720px}
        .public-header{text-align:center;margin-bottom:var(--space-6)}
        .public-logo{display:inline-flex;align-items:center;gap:var(--space-3);text-decoration:none;color:var(--color-text);margin-bottom:var(--space-4)}
        .public-logo svg{color:var(--color-primary)}
        .public-logo-text{font-size:var(--text-lg);font-weight:700;letter-spacing:-.02em}
    </style>
</head>
<body>
<div class="public-layout">
    <div class="public-card">
        <div class="public-header">
            <a href="{{ route('home') }}" class="public-logo">
                <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
                    <rect width="28" height="28" rx="7" fill="currentColor" opacity=".12"/>
                    <path d="M7 10 L14 6 L21 10 L21 18 L14 22 L7 18 Z" stroke="currentColor" stroke-width="1.6" fill="none"/>
                    <path d="M7 10 L14 14 L21 10" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M14 14 L14 22" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                <span class="public-logo-text">FBA Umlagerung</span>
            </a>
        </div>

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
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
