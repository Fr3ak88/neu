@extends('layouts.app')

@section('title', 'Benutzer hinzufügen')

@section('content')
<div class="page-header">
    <div class="page-title">Benutzer hinzufügen</div>
    <div class="page-subtitle">Neues Team-Mitglied erstellen</div>
</div>

<div style="max-width:32rem">
    <div class="card">
        <form method="POST" action="{{ route('firmenadmin.users.store') }}">
            @csrf

            @if ($errors->any())
                <div style="display:flex;align-items:center;gap:var(--space-2);padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);margin-bottom:var(--space-4);font-size:var(--text-sm);background:var(--color-error-highlight);color:var(--color-error);border:1px solid var(--color-error)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="field">
                <label for="name">Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="inp" value="{{ old('name') }}" required>
            </div>

            <div class="field">
                <label for="email">E-Mail <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="inp" value="{{ old('email') }}" required>
            </div>

            <div class="field">
                <label for="password">Passwort <span class="req">*</span></label>
                <input type="password" id="password" name="password" class="inp" placeholder="Mind. 8 Zeichen" required autocomplete="new-password">
            </div>

            <div class="field">
                <label for="password_confirmation">Passwort bestätigen <span class="req">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="inp" placeholder="Passwort wiederholen" required autocomplete="new-password">
            </div>

            <div class="field">
                <label for="role">Rolle <span class="req">*</span></label>
                <select id="role" name="role" class="inp" required>
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Benutzer</option>
                    <option value="firmenadmin" {{ old('role') === 'firmenadmin' ? 'selected' : '' }}>Firmen-Admin</option>
                </select>
            </div>

            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6)">
                <button type="submit" class="btn btn-primary">Erstellen</button>
                <a href="{{ route('firmenadmin.users.index') }}" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
