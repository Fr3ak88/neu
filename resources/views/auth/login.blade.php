<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Anmelden — Fritzler-Solution</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        const saved = localStorage.getItem('theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
    </script>
    @vite(['resources/css/fba-design.css'])
    <style>
        .login{display:flex;flex-direction:column;min-height:100dvh;align-items:center;justify-content:center;padding:var(--space-6);background:var(--color-bg)}
        .login-card{width:100%;max-width:24rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:var(--space-8);box-shadow:var(--shadow-lg)}
        .login-logo{display:flex;align-items:center;justify-content:center;gap:var(--space-3);margin-bottom:var(--space-8);text-decoration:none;color:var(--color-text)}
        .login-logo svg{color:var(--color-primary)}
        .login-logo-text{font-size:var(--text-sm);font-weight:600;letter-spacing:-.01em}
        .login-title{font-size:var(--text-lg);font-weight:700;text-align:center;margin-bottom:var(--space-2);color:var(--color-text)}
        .login-sub{font-size:var(--text-sm);color:var(--color-text-muted);text-align:center;margin-bottom:var(--space-6)}
        .login-field{display:flex;flex-direction:column;gap:var(--space-2);margin-bottom:var(--space-4)}
        .login-field label{font-size:var(--text-sm);font-weight:500;color:var(--color-text-muted)}
        .login-input{padding:var(--space-3) var(--space-4);border:1px solid var(--color-border);border-radius:var(--radius-md);background:var(--color-surface-2);font-size:var(--text-sm);color:var(--color-text);width:100%}
        .login-input:focus{border-color:var(--color-primary);outline:none}
        .login-input::placeholder{color:var(--color-text-faint)}
        .login-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:var(--space-2);padding:var(--space-3) var(--space-5);border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:500;cursor:pointer;transition:all var(--transition);border:none;min-height:44px;background:var(--color-primary);color:#fff;margin-top:var(--space-6)}
        .login-btn:hover{background:var(--color-primary-hover)}
        .login-btn:disabled{opacity:.4;cursor:not-allowed}
        .login-error{display:flex;align-items:center;gap:var(--space-2);padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);margin-bottom:var(--space-4);font-size:var(--text-sm);background:var(--color-error-highlight);color:var(--color-error);border:1px solid var(--color-error)}
        .login-footer{margin-top:var(--space-6);text-align:center;font-size:var(--text-xs);color:var(--color-text-faint)}
        .login-footer a{color:var(--color-primary);text-decoration:none}
        .login-footer a:hover{text-decoration:underline}
        .login-theme{position:fixed;top:var(--space-4);right:var(--space-4);display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:var(--radius-md);color:var(--color-text-muted);background:var(--color-surface);border:1px solid var(--color-border);cursor:pointer;transition:all var(--transition);box-shadow:var(--shadow-sm)}
        .login-theme:hover{background:var(--color-surface-dynamic);color:var(--color-text)}
    </style>
</head>
<body>
    <button class="login-theme" onclick="toggleTheme()" aria-label="Theme wechseln">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    </button>

    <div class="login">
        <div class="login-card">
            <a href="/" class="login-logo">
                <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
                    <rect width="28" height="28" rx="7" fill="currentColor" opacity=".12"/>
                    <path d="M7 10 L14 6 L21 10 L21 18 L14 22 L7 18 Z" stroke="currentColor" stroke-width="1.6" fill="none"/>
                    <path d="M7 10 L14 14 L21 10" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M14 14 L14 22" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                <span class="login-logo-text">Fritzler-Solution</span>
            </a>

            <div class="login-title">Willkommen zurück</div>
            <div class="login-sub">Melde dich an, um fortzufahren</div>

            @if ($errors->any())
                <div class="login-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="login-field">
                    <label for="email">E-Mail</label>
                    <input type="email" id="email" name="email" class="login-input" placeholder="name@beispiel.de" value="{{ old('email') }}" required autofocus autocomplete="email">
                </div>
                <div class="login-field">
                    <label for="password">Passwort</label>
                    <input type="password" id="password" name="password" class="login-input" placeholder="Passwort eingeben" required autocomplete="current-password">
                </div>
                <button type="submit" class="login-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Anmelden
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('register') }}">Konto erstellen</a> · <a href="/">← Zurück zur Startseite</a>
            </div>
        </div>
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
