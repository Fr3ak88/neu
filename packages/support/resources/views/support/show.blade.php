@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-8)">
    <div>
        <div class="page-title">
            <span style="font-family:var(--font-mono);font-size:var(--text-sm);color:var(--color-text-faint);margin-right:var(--space-2)">#{{ $ticket->id }}</span>
            {{ $ticket->subject }}
        </div>
        <div class="page-subtitle">Erstellt am {{ $ticket->created_at->format('d.m.Y H:i') }}</div>
    </div>
    <a href="{{ route('support.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left" width="16" height="16"></i> Zurück
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-6);align-items:start">
    <div style="display:flex;flex-direction:column;gap:var(--space-6)">
        <div class="card">
            <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4)">
                <div style="width:36px;height:36px;border-radius:var(--radius-full);display:flex;align-items:center;justify-content:center;
                    @if($ticket->status === 'open') background:var(--color-warning-highlight)
                    @elseif($ticket->status === 'in_progress') background:var(--color-primary-highlight)
                    @else background:var(--color-success-highlight) @endif">
                    <i data-lucide="@if($ticket->status === 'open') alert-circle @elseif($ticket->status === 'in_progress') clock @else check-circle @endif" width="18" height="18"
                        style="@if($ticket->status === 'open') color:var(--color-warning) @elseif($ticket->status === 'in_progress') color:var(--color-primary) @else color:var(--color-success) @endif"></i>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em">Nachricht</div>
                    <div style="font-size:var(--text-sm);font-weight:600">
                        @if($ticket->status === 'open')
                            <span class="badge badge-warning">{{ $ticket->status_label }}</span>
                        @elseif($ticket->status === 'in_progress')
                            <span class="badge badge-info">{{ $ticket->status_label }}</span>
                        @else
                            <span class="badge badge-success">{{ $ticket->status_label }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="padding:var(--space-4);background:var(--color-surface-offset);border-radius:var(--radius-lg);line-height:1.7;color:var(--color-text)">
                <p style="white-space:pre-wrap;margin:0">{{ $ticket->message }}</p>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:var(--space-4)">
        <div class="card">
            <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-4)">Status ändern</div>
            <form method="POST" action="{{ route('support.update', $ticket) }}">
                @csrf
                @method('PUT')
                <div style="display:flex;gap:var(--space-3)">
                    <select name="status" class="inp" style="flex:1">
                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Offen</option>
                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                        <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Geschlossen</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0">
                        <i data-lucide="check" width="14" height="14"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--space-4)">Details</div>
            <div style="display:flex;flex-direction:column;gap:var(--space-3)">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Kunde</span>
                    <span style="font-size:var(--text-sm);font-weight:500">{{ $ticket->customer_email ?? '—' }}</span>
                </div>
                <div style="height:1px;background:var(--color-border);margin:var(--space-1) 0"></div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Priorität</span>
                    <span style="font-size:var(--text-sm);font-weight:500">{{ $ticket->priority_label }}</span>
                </div>
                <div style="height:1px;background:var(--color-border);margin:var(--space-1) 0"></div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Erstellt</span>
                    <span style="font-size:var(--text-sm)">{{ $ticket->created_at->format('d.m.Y H:i') }}</span>
                </div>
                <div style="height:1px;background:var(--color-border);margin:var(--space-1) 0"></div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Aktualisiert</span>
                    <span style="font-size:var(--text-sm)">{{ $ticket->updated_at->format('d.m.Y H:i') }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('support.destroy', $ticket) }}" onsubmit="return confirm('Ticket wirklich löschen?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" style="width:100%">
                <i data-lucide="trash-2" width="14" height="14"></i> Ticket löschen
            </button>
        </form>
    </div>
</div>
@endsection
