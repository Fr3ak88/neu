# FBA Umlagerung SaaS — Lokales Setup

## Voraussetzungen
- Laravel Herd (Windows)
- DBngin mit MySQL auf Port 3307
- PHP 8.4 (über Herd)

---

## 1. Pakete installieren

```bash
composer require jlevers/selling-partner-api laravel/sanctum laravel/reverb
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan reverb:install
```

---

## 2. .env konfigurieren

Inhalt aus `.env.local.example` in `.env` kopieren und anpassen:

```bash
cp .env.local.example .env
php artisan key:generate
```

---

## 3. Dateien einkopieren

Alle Dateien aus diesem Paket in das jeweilige Verzeichnis des Laravel-Projekts kopieren:

| Quelle | Ziel |
|--------|------|
| `database/migrations/*` | `database/migrations/` |
| `app/Models/*` | `app/Models/` |
| `app/Traits/*` | `app/Traits/` |
| `app/Services/*` | `app/Services/` |
| `app/Http/Controllers/*` | `app/Http/Controllers/` |
| `app/Http/Middleware/*` | `app/Http/Middleware/` |
| `app/Jobs/*` | `app/Jobs/` |
| `app/Providers/AppServiceProvider.php` | `app/Providers/` (überschreiben) |
| `routes/web.php` | `routes/web.php` (überschreiben) |
| `config/fba.php` | `config/` |
| `resources/views/*` | `resources/views/` |

---

## 4. Middleware registrieren

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'tenant' => \App\Http\Middleware\TenantMiddleware::class,
    ]);
})
```

---

## 5. Datenbank migrieren

```bash
php artisan migrate
```

---

## 6. Ersten Tenant + User anlegen (Tinker)

```bash
php artisan tinker
```

```php
$tenant = \App\Models\Tenant::create(['name' => 'Mein Shop', 'slug' => 'mein-shop']);
\App\Models\User::create([
    'name'      => 'Admin',
    'email'     => 'admin@test.de',
    'password'  => bcrypt('password'),
    'tenant_id' => $tenant->id,
]);
```

---

## 7. App aufrufen

```
http://fba-saas.test
# oder
http://localhost:8000 (php artisan serve)
```

---

## 8. Täglicher Workflow (lokal)

`QUEUE_CONNECTION=sync` → kein Worker nötig, alles läuft synchron.

Optional mit echten Queues:
```bash
# Terminal 1 (automatisch durch Herd)
# Terminal 2
php artisan queue:work --queue=fba-inbound,default
```

---

## Hinweis: Fake-Service lokal

Solange `APP_ENV=local` ist, wird automatisch `FakeInboundService` statt
dem echten SP-API Service verwendet (kein Amazon-Account nötig zum Testen).

Echter Service wird automatisch in Production aktiviert.
