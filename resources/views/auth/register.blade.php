<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrieren — Fritzler-Solution</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        const saved = localStorage.getItem('theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
    </script>
    @vite(['resources/css/fba-design.css'])
    <style>
        .register{display:flex;flex-direction:column;min-height:100dvh;align-items:center;justify-content:center;padding:var(--space-6);background:var(--color-bg)}
        .register-card{width:100%;max-width:24rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:var(--space-8);box-shadow:var(--shadow-lg)}
        .register-logo{display:flex;align-items:center;justify-content:center;gap:var(--space-3);margin-bottom:var(--space-8);text-decoration:none;color:var(--color-text)}
        .register-logo svg{color:var(--color-primary)}
        .register-logo-text{font-size:var(--text-sm);font-weight:600;letter-spacing:-.01em}
        .register-title{font-size:var(--text-lg);font-weight:700;text-align:center;margin-bottom:var(--space-2);color:var(--color-text)}
        .register-sub{font-size:var(--text-sm);color:var(--color-text-muted);text-align:center;margin-bottom:var(--space-6)}
        .register-field{display:flex;flex-direction:column;gap:var(--space-2);margin-bottom:var(--space-4)}
        .register-field label{font-size:var(--text-sm);font-weight:500;color:var(--color-text-muted)}
        .register-input{padding:var(--space-3) var(--space-4);border:1px solid var(--color-border);border-radius:var(--radius-md);background:var(--color-surface-2);font-size:var(--text-sm);color:var(--color-text);width:100%}
        .register-input:focus{border-color:var(--color-primary);outline:none}
        .register-input::placeholder{color:var(--color-text-faint)}
        .register-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:var(--space-2);padding:var(--space-3) var(--space-5);border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:500;cursor:pointer;transition:all var(--transition);border:none;min-height:44px;background:var(--color-primary);color:#fff;margin-top:var(--space-6)}
        .register-btn:hover{background:var(--color-primary-hover)}
        .register-btn:disabled{opacity:.4;cursor:not-allowed}
        .register-error{display:flex;align-items:center;gap:var(--space-2);padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);margin-bottom:var(--space-4);font-size:var(--text-sm);background:var(--color-error-highlight);color:var(--color-error);border:1px solid var(--color-error)}
        .register-footer{margin-top:var(--space-6);text-align:center;font-size:var(--text-xs);color:var(--color-text-faint)}
        .register-footer a{color:var(--color-primary);text-decoration:none}
        .register-footer a:hover{text-decoration:underline}
        .register-theme{position:fixed;top:var(--space-4);right:var(--space-4);display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:var(--radius-md);color:var(--color-text-muted);background:var(--color-surface);border:1px solid var(--color-border);cursor:pointer;transition:all var(--transition);box-shadow:var(--shadow-sm)}
        .register-theme:hover{background:var(--color-surface-dynamic);color:var(--color-text)}
        .register-divider{display:flex;align-items:center;gap:var(--space-3);margin:var(--space-5) 0 var(--space-4);color:var(--color-text-faint);font-size:var(--text-xs);font-weight:500;text-transform:uppercase;letter-spacing:.06em}
        .register-divider::before,.register-divider::after{content:'';flex:1;height:1px;background:var(--color-border)}
        .register-row{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4)}
    </style>
</head>
<body>
    <button class="register-theme" onclick="toggleTheme()" aria-label="Theme wechseln">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    </button>

    <div class="register">
        <div class="register-card">
            <a href="/" class="register-logo">
                <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
                    <rect width="28" height="28" rx="7" fill="currentColor" opacity=".12"/>
                    <path d="M7 10 L14 6 L21 10 L21 18 L14 22 L7 18 Z" stroke="currentColor" stroke-width="1.6" fill="none"/>
                    <path d="M7 10 L14 14 L21 10" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M14 14 L14 22" stroke="currentColor" stroke-width="1.6"/>
                </svg>
                <span class="register-logo-text">Fritzler-Solutionn</span>
            </a>

            <div class="register-title">Konto erstellen</div>
            <div class="register-sub">Registriere dich, um loszulegen</div>

            @if ($errors->any())
                <div class="register-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="register-field">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="register-input" placeholder="Vollständiger Name" value="{{ old('name') }}" required autofocus autocomplete="name">
                </div>
                <div class="register-field">
                    <label for="email">E-Mail *</label>
                    <input type="email" id="email" name="email" class="register-input" placeholder="name@beispiel.de" value="{{ old('email') }}" required autocomplete="email">
                </div>
                <div class="register-field">
                    <label for="password">Passwort *</label>
                    <input type="password" id="password" name="password" class="register-input" placeholder="Mind. 8 Zeichen" required autocomplete="new-password">
                </div>
                <div class="register-field">
                    <label for="password_confirmation">Passwort bestätigen *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="register-input" placeholder="Passwort wiederholen" required autocomplete="new-password">
                </div>

                <div class="register-divider">Adressdaten</div>

                <div class="register-field">
                    <label for="company">Firma <span class="req">*</span></label>
                    <input type="text" id="company" name="company" class="register-input" placeholder="Firmenname GmbH" value="{{ old('company') }}" required>
                </div>
                <div class="register-field">
                    <label for="street">Straße und Hausnummer <span class="req">*</span></label>
                    <input type="text" id="street" name="street" class="register-input" placeholder="Musterstraße 1" value="{{ old('street') }}" required>
                </div>
                <div class="register-row">
                    <div class="register-field">
                        <label for="zip">PLZ <span class="req">*</span></label>
                        <input type="text" id="zip" name="zip" class="register-input" placeholder="12345" value="{{ old('zip') }}" required>
                    </div>
                    <div class="register-field">
                        <label for="city">Stadt <span class="req">*</span></label>
                        <input type="text" id="city" name="city" class="register-input" placeholder="Berlin" value="{{ old('city') }}" required>
                    </div>
                </div>
                <div class="register-row">
                    <div class="register-field">
                        <label for="country">Land <span class="req">*</span></label>
                        <select id="country" name="country" class="register-input" required>
                            <option value="DE" {{ old('country', 'DE') === 'DE' ? 'selected' : '' }}>Deutschland</option>
                            <option value="AT" {{ old('country') === 'AT' ? 'selected' : '' }}>Österreich</option>
                            <option value="CH" {{ old('country') === 'CH' ? 'selected' : '' }}>Schweiz</option>
                        </select>
                    </div>
                    <div class="register-field">
                        <label for="phone">Telefon <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" class="register-input" placeholder="+49 170 1234567" value="{{ old('phone') }}" required>
                    </div>
                </div>
                <button type="submit" class="register-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Konto erstellen
                </button>
            </form>

            <div class="register-footer">
                Bereits ein Konto? <a href="{{ route('login') }}">Anmelden</a><a href="/">← Zurück zur Startseite</a>
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
