<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fritzler-Solution</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        const saved = localStorage.getItem('theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
    </script>
    @vite(['resources/css/fba-design.css'])
    <style>
        .lp{display:flex;flex-direction:column;min-height:100dvh}
        .lp-nav{display:flex;align-items:center;justify-content:space-between;padding:var(--space-4) var(--space-8);background:var(--color-surface);border-bottom:1px solid var(--color-border)}
        .lp-nav-logo{display:flex;align-items:center;gap:var(--space-3);text-decoration:none;color:var(--color-text)}
        .lp-nav-logo svg{color:var(--color-primary)}
        .lp-nav-brand{font-size:var(--text-sm);font-weight:600;letter-spacing:-.01em}
        .lp-nav-links{display:flex;align-items:center;gap:var(--space-4)}
        .lp-nav-link{font-size:var(--text-sm);color:var(--color-text-muted);text-decoration:none;padding:var(--space-2) var(--space-3);border-radius:var(--radius-md);transition:all var(--transition)}
        .lp-nav-link:hover{color:var(--color-text);background:var(--color-surface-dynamic)}
        .lp-hero{display:flex;flex-direction:column;align-items:center;text-align:center;padding:var(--space-16) var(--space-8) var(--space-12);max-width:52rem;margin:0 auto}
        .lp-hero h1{font-size:clamp(2rem,1.5rem + 2.5vw,3.5rem);font-weight:700;letter-spacing:-.03em;line-height:1.15;margin-bottom:var(--space-5);color:var(--color-text)}
        .lp-hero h1 span{color:var(--color-primary)}
        .lp-hero p{font-size:var(--text-lg);color:var(--color-text-muted);max-width:44ch;margin-bottom:var(--space-8);line-height:1.6}
        .lp-hero-actions{display:flex;gap:var(--space-3);flex-wrap:wrap;justify-content:center}
        .lp-section{padding:var(--space-16) var(--space-8);max-width:64rem;margin:0 auto}
        .lp-section-label{font-size:var(--text-xs);font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--color-primary);margin-bottom:var(--space-3)}
        .lp-section-title{font-size:var(--text-xl);font-weight:700;letter-spacing:-.02em;margin-bottom:var(--space-3);color:var(--color-text)}
        .lp-section-desc{font-size:var(--text-base);color:var(--color-text-muted);max-width:50ch;margin-bottom:var(--space-10);line-height:1.6}
        .lp-features{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-6)}
        .lp-feature{background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:var(--space-6);transition:box-shadow var(--transition),border-color var(--transition)}
        .lp-feature:hover{box-shadow:var(--shadow-md);border-color:var(--color-primary)}
        .lp-feature-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius-lg);background:var(--color-primary-highlight);color:var(--color-primary);margin-bottom:var(--space-4)}
        .lp-feature h3{font-size:var(--text-base);font-weight:600;margin-bottom:var(--space-2);color:var(--color-text)}
        .lp-feature p{font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.6}
        .lp-cta{display:flex;flex-direction:column;align-items:center;text-align:center;padding:var(--space-16) var(--space-8);background:var(--color-surface);border-top:1px solid var(--color-border)}
        .lp-cta h2{font-size:var(--text-xl);font-weight:700;letter-spacing:-.02em;margin-bottom:var(--space-3)}
        .lp-cta p{font-size:var(--text-base);color:var(--color-text-muted);margin-bottom:var(--space-6);max-width:40ch}
        .lp-footer{padding:var(--space-6) var(--space-8);border-top:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;font-size:var(--text-xs);color:var(--color-text-faint)}
        .lp-footer a{color:var(--color-text-muted);text-decoration:none}
        .lp-footer a:hover{color:var(--color-primary)}
        .lp-theme-btn{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:var(--radius-md);color:var(--color-text-muted);background:none;border:none;cursor:pointer;transition:all var(--transition)}
        .lp-theme-btn:hover{background:var(--color-surface-dynamic);color:var(--color-text)}
        @media(max-width:900px){.lp-features{grid-template-columns:1fr}}
        @media(max-width:600px){.lp-nav{padding:var(--space-3) var(--space-4)}.lp-hero{padding:var(--space-10) var(--space-4) var(--space-8)}.lp-section{padding:var(--space-10) var(--space-4)}.lp-footer{flex-direction:column;gap:var(--space-3);text-align:center}}
    </style>
</head>
<body>
<div class="lp">
    <nav class="lp-nav">
        <a href="/" class="lp-nav-logo">
            <span class="lp-nav-brand">Fritzler-Solution</span>
        </a>
        <div class="lp-nav-links">
            <button class="lp-theme-btn" onclick="toggleTheme()" aria-label="Theme wechseln">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            </button>
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('customer.login') }}" class="lp-nav-link">Kundenbereich</a>
                    <a href="{{ route('login') }}" class="lp-nav-link">Anmelden</a>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Loslegen</a>
                @endauth
            @endif
        </div>
    </nav>

    <section class="lp-hero">
        <h1>Fritzler-Solution<br><span>einfach &amp; schnell</span></h1>
        <p>Willkommen bei Fritzler-Solution, Ihrer Anlaufstelle für effiziente Lösungen. Entdecken Sie unsere Dienstleistungen und starten Sie noch heute.</p>
        <div class="lp-hero-actions">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Zum Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Jetzt starten</a>
                @endauth
            @endif
        </div>
    </section>

    

    <section class="lp-cta">
        <h2>Bereit loszulegen?</h2>
        <p>Erstelle dein Konto und starte sofort.</p>
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary">Zum Dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary">Kostenlos anmelden</a>
            @endauth
        @endif
    </section>

    <footer class="lp-footer">
        <span>Fritzler-Solution</span>
    </footer>
</div>

<script>
function toggleTheme() {
    const h = document.documentElement;
    const cur = h.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    h.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}
</script>
</body>
</html>
