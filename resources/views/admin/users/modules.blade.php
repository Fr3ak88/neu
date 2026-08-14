@extends('layouts.admin')

@section('title', 'Module — ' . $user->name)

@section('content')
<div class="page-header">
    <div class="page-title">Module für {{ $user->name }}</div>
    <div class="page-subtitle">Aktivierte Module verwalten</div>
</div>

<div style="max-width:32rem">
    <div class="card">
        <form method="POST" action="{{ route('admin.users.update-modules', $user) }}">
            @csrf
            @method('PUT')

            @foreach($available as $key => $label)
                @continue($key === 'support')
                <label style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3) var(--space-4);border:1px solid var(--color-border);border-radius:var(--radius-md);margin-bottom:var(--space-3);cursor:pointer;transition:all var(--transition)">
                    <input type="checkbox" name="modules[]" value="{{ $key }}" {{ in_array($key, $user->modules ?? []) ? 'checked' : '' }} style="width:16px;height:16px">
                    <div>
                        <div style="font-size:var(--text-sm);font-weight:500;color:var(--color-text)">{{ $label }}</div>
                        <div style="font-size:var(--text-xs);color:var(--color-text-muted)">{{ $key }}</div>
                    </div>
                </label>
            @endforeach

            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6)">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
