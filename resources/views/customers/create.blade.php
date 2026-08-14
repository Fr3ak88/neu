@extends('layouts.app')

@section('title', 'Neuer Kunde')

@section('content')
<div class="page-header">
    <div class="page-title">Neuer Kunde</div>
    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left" width="16" height="16"></i> Zurück
    </a>
</div>

<form method="POST" action="{{ route('customers.store') }}">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Kundendaten</div>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Vor- und Nachname">
                                @error('name') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Firma</label>
                                <input type="text" name="company" class="form-input" value="{{ old('company') }}" placeholder="Firmenname">
                                @error('company') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">E-Mail <span style="color:var(--color-error)">*</span></label>
                                <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="name@beispiel.de" required>
                                @error('email') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" placeholder="+49 ...">
                                @error('phone') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="field">
                            <label class="form-label">Straße, Hausnummer</label>
                            <input type="text" name="street" class="form-input" value="{{ old('street') }}" placeholder="Musterstraße 1">
                            @error('street') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">PLZ</label>
                                <input type="text" name="zip" class="form-input" value="{{ old('zip') }}" placeholder="12345">
                                @error('zip') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Stadt</label>
                                <input type="text" name="city" class="form-input" value="{{ old('city') }}" placeholder="Musterstadt">
                                @error('city') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Land</label>
                                <input type="text" name="country" class="form-input" value="{{ old('country', 'DE') }}" maxlength="2" placeholder="DE">
                                @error('country') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Zugangsdaten</div>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="field">
                            <label class="form-label">Passwort <span style="color:var(--color-error)">*</span></label>
                            <input type="password" name="password" class="form-input" placeholder="Mindestens 6 Zeichen" required>
                            @error('password') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label class="form-label">Passwort bestätigen <span style="color:var(--color-error)">*</span></label>
                            <input type="password" name="password_confirmation" class="form-input" placeholder="Passwort wiederholen" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <div class="card-title">Notizen</div>
                </div>
                <div class="card-body">
                    <div class="field">
                        <textarea name="notes" class="form-input" rows="4" placeholder="Interne Notizen zum Kunden...">{{ old('notes') }}</textarea>
                        @error('notes') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:var(--space-4)">
                <i data-lucide="check" width="16" height="16"></i> Kunde anlegen
            </button>
        </div>
    </div>
</form>
@endsection
