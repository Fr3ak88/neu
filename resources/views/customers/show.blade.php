@extends('layouts.app')

@section('title', $customer->name ?? $customer->email)

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">{{ $customer->name ?? $customer->email }}</div>
        <div class="page-subtitle">Kunde seit {{ $customer->created_at->format('d.m.Y') }}</div>
    </div>
    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left" width="16" height="16"></i> Zurück
    </a>
</div>

<form method="POST" action="{{ route('customers.update', $customer) }}">
    @csrf
    @method('PUT')

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
                                <input type="text" name="name" class="form-input" value="{{ old('name', $customer->name) }}" placeholder="Vor- und Nachname">
                                @error('name') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Firma</label>
                                <input type="text" name="company" class="form-input" value="{{ old('company', $customer->company) }}" placeholder="Firmenname">
                                @error('company') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">E-Mail <span style="color:var(--color-error)">*</span></label>
                                <input type="email" name="email" class="form-input" value="{{ old('email', $customer->email) }}" required>
                                @error('email') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="phone" class="form-input" value="{{ old('phone', $customer->phone) }}" placeholder="+49 ...">
                                @error('phone') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="field">
                            <label class="form-label">Straße, Hausnummer</label>
                            <input type="text" name="street" class="form-input" value="{{ old('street', $customer->street) }}" placeholder="Musterstraße 1">
                            @error('street') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">PLZ</label>
                                <input type="text" name="zip" class="form-input" value="{{ old('zip', $customer->zip) }}" placeholder="12345">
                                @error('zip') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Stadt</label>
                                <input type="text" name="city" class="form-input" value="{{ old('city', $customer->city) }}" placeholder="Musterstadt">
                                @error('city') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label">Land</label>
                                <input type="text" name="country" class="form-input" value="{{ old('country', $customer->country) }}" maxlength="2" placeholder="DE">
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
                    <div class="card-title">Notizen</div>
                </div>
                <div class="card-body">
                    <div class="field">
                        <textarea name="notes" class="form-input" rows="4" placeholder="Interne Notizen zum Kunden...">{{ old('notes', $customer->notes) }}</textarea>
                        @error('notes') <span style="color:var(--color-error);font-size:var(--text-xs)">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:var(--space-4)">
                <i data-lucide="check" width="16" height="16"></i> Speichern
            </button>
        </div>
    </div>
</form>

<div style="margin-top:var(--space-8)">
    <div class="card" style="border-color:var(--color-error)">
        <div class="card-header">
            <div class="card-title" style="color:var(--color-error)">Gefahrenbereich</div>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div class="font-medium">Kunde löschen</div>
                    <div class="text-muted" style="font-size:var(--text-sm)">Dies kann nicht rückgängig gemacht werden.</div>
                </div>
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Kunde wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i data-lucide="trash-2" width="16" height="16"></i> Löschen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
