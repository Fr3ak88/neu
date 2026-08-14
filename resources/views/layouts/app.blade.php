<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FBA Umlagerung')</title>
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
        <a class="topbar-logo" href="{{ route('dashboard') }}">

            <span class="topbar-logo-text">Fritzler-Solution</span>
        </a>
        <div class="topbar-right">
            <button class="icon-btn" onclick="toggleTheme()" aria-label="Theme wechseln">
                <i data-lucide="sun" width="18" height="18"></i>
            </button>
            <button class="icon-btn" aria-label="Benachrichtigungen">
                <i data-lucide="bell" width="18" height="18"></i>
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
            @php
                $user = auth()->user();
                $modules = $user->modules ?? [];
            @endphp

            <div class="sidebar-section">
                <div class="sidebar-section-label">Navigation</div>
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" width="16" height="16"></i> Dashboard
                </a>

                @if(in_array('customers', $modules))
                <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <i data-lucide="users" width="16" height="16"></i> Kunden
                </a>
                @endif

                @if(in_array('invoices', $modules))
                <a href="{{ route('auftraege.index') }}" class="nav-item {{ request()->routeIs('auftraege.*') ? 'active' : '' }}">
                    <i data-lucide="repeat" width="16" height="16"></i> Aufträge
                </a>
                <a href="{{ route('rechnungen.index') }}" class="nav-item {{ request()->routeIs('rechnungen.*') ? 'active' : '' }}">
                    <i data-lucide="file-text" width="16" height="16"></i> Rechnungen
                </a>
                @endif
                
                @if(in_array('fba_shipments', $modules))
                @php
                    $fbaActive = request()->routeIs('fba-shipments.*') || request()->routeIs('fba-shipments') || request()->routeIs('fba-inventory*');
                @endphp
                <div class="nav-group {{ $fbaActive ? 'open' : '' }}" id="navFba">
                    <button class="nav-group-toggle" onclick="document.getElementById('navFba').classList.toggle('open')">
                        <i data-lucide="package" width="16" height="16"></i> FBA Umlagerung
                        <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <div class="nav-group-children">
                        <a href="{{ route('fba-shipments.index') }}" class="nav-item {{ request()->routeIs('fba-shipments.index') ? 'active' : '' }}">
                            <i data-lucide="list" width="14" height="14"></i> Umlagerungen
                        </a>
                        <a href="{{ route('fba-shipments.create') }}" class="nav-item {{ request()->routeIs('fba-shipments.create') ? 'active' : '' }}">
                            <i data-lucide="plus-circle" width="14" height="14"></i> Neue Umlagerung
                        </a>
                        <a href="{{ route('fba-inventory.index') }}" class="nav-item {{ request()->routeIs('fba-inventory*') ? 'active' : '' }}">
                            <i data-lucide="boxes" width="14" height="14"></i> FBA Bestand
                        </a>
                        <a href="{{ route('fba-shipments.plans') }}" class="nav-item {{ request()->routeIs('fba-shipments.plans*') ? 'active' : '' }}">
                            <i data-lucide="cloud" width="14" height="14"></i> Amazon Plans
                        </a>
                    </div>
                </div>
                @endif
                @if(in_array('wms', $modules))
                @php
                    $wmsActive = request()->routeIs('wms.*');
                @endphp
                <div class="nav-group {{ $wmsActive ? 'open' : '' }}" id="navWms">
                    <button class="nav-group-toggle" onclick="document.getElementById('navWms').classList.toggle('open')">
                        <i data-lucide="warehouse" width="16" height="16"></i> Storelogix
                        <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <div class="nav-group-children">
                        <a href="{{ route('wms.dashboard') }}" class="nav-item {{ request()->routeIs('wms.dashboard') ? 'active' : '' }}">
                            <i data-lucide="layout-dashboard" width="14" height="14"></i> Dashboard
                        </a>
                        <a href="{{ route('wms.products.index') }}" class="nav-item {{ request()->routeIs('wms.products.*') ? 'active' : '' }}">
                            <i data-lucide="boxes" width="14" height="14"></i> Bestände
                        </a>
                        <a href="{{ route('wms.orders.index') }}" class="nav-item {{ request()->routeIs('wms.orders.*') ? 'active' : '' }}">
                            <i data-lucide="shopping-cart" width="14" height="14"></i> Bestellungen
                        </a>
                        <a href="{{ route('wms.shipments.index') }}" class="nav-item {{ request()->routeIs('wms.shipments.*') ? 'active' : '' }}">
                            <i data-lucide="truck" width="14" height="14"></i> Versandaufträge
                        </a>
                        <a href="{{ route('wms.returns.index') }}" class="nav-item {{ request()->routeIs('wms.returns.*') ? 'active' : '' }}">
                            <i data-lucide="rotate-ccw" width="14" height="14"></i> Retouren
                        </a>
                        <a href="{{ route('wms.sync.index') }}" class="nav-item {{ request()->routeIs('wms.sync*') ? 'active' : '' }}">
                            <i data-lucide="refresh-cw" width="14" height="14"></i> Sync
                        </a>
                    </div>
                </div>
                @endif

            </div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-section" style="margin-top:auto">
                <div class="sidebar-section-label">Konto</div>
                <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile*') ? 'active' : '' }}">
                    <i data-lucide="user" width="16" height="16"></i> Mein Profil
                </a>

                @if($user->canManageUsers())
                <a href="{{ route('firmenadmin.users.index') }}" class="nav-item {{ request()->routeIs('firmenadmin.*') ? 'active' : '' }}">
                    <i data-lucide="users" width="16" height="16"></i> Team verwalten
                </a>
                @endif

                @php
                    $settingsActive = request()->routeIs('amazon-accounts*') || request()->routeIs('jtl-connect*') || request()->routeIs('jtl-cloud*') || request()->routeIs('jtl.settings*') || request()->routeIs('storlogix-connect*') || request()->routeIs('wms.sync*') || request()->routeIs('email-settings*');
                @endphp
                <div class="nav-group {{ $settingsActive ? 'open' : '' }}" id="navSettings">
                    <button class="nav-group-toggle" onclick="document.getElementById('navSettings').classList.toggle('open')">
                        <i data-lucide="settings" width="16" height="16"></i> Einstellungen
                        <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <div class="nav-group-children">
                        @if(in_array('fba_shipments', $modules))
                        <a href="{{ route('amazon-accounts.index') }}" class="nav-item {{ request()->routeIs('amazon-accounts*') ? 'active' : '' }}">
                            <i data-lucide="plug" width="14" height="14"></i> Amazon Connect
                        </a>
                        @endif
                        @if(in_array('fba_shipments', $modules))
                        <a href="{{ route('jtl.settings') }}" class="nav-item {{ request()->routeIs('jtl.settings*') || request()->routeIs('jtl-connect*') || request()->routeIs('jtl-cloud*') ? 'active' : '' }}">
                            <i data-lucide="settings" width="14" height="14"></i> JTL-Wawi
                        </a>
                        @endif
                        @if(in_array('wms', $modules))
                        <a href="{{ route('storlogix-connect.show') }}" class="nav-item {{ request()->routeIs('storlogix-connect*') ? 'active' : '' }}">
                            <i data-lucide="warehouse" width="14" height="14"></i> Storelogix Connect
                        </a>
                        @endif
                        <a href="{{ route('email-settings.edit') }}" class="nav-item {{ request()->routeIs('email-settings*') ? 'active' : '' }}">
                            <i data-lucide="mail" width="14" height="14"></i> E-Mail
                        </a>
                    </div>
                </div>
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