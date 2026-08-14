@extends('layouts.app')

@section('title', $rechnung->rechnungsnummer)

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">{{ $rechnung->rechnungsnummer }}</div>
        <div class="page-subtitle">Erstellt am {{ $rechnung->datum?->format('d.m.Y') ?? '–' }} · Fällig {{ $rechnung->faelligkeitsdatum?->format('d.m.Y') ?? '–' }}</div>
    </div>
    <div style="display:flex;gap:var(--space-3)">
        <a href="{{ route('rechnungen.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left" width="16" height="16"></i> Zurück
        </a>
        @if($rechnung->isEditable())
            <a href="{{ route('rechnungen.edit', $rechnung) }}" class="btn btn-secondary">
                <i data-lucide="pencil" width="16" height="16"></i> Bearbeiten
            </a>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:3fr 2fr;gap:var(--space-6);align-items:start">
    <div>
        {{-- Details --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i data-lucide="info" width="16" height="16"></i> Details</div>
            </div>
            <div class="card-body" style="padding:0">
                {{-- Rechnung --}}
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-divider)">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Rechnung</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Nr.</div>
                            <div style="font-family:var(--font-mono);font-weight:500">{{ $rechnung->rechnungsnummer }}</div>
                        </div>
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Datum</div>
                            <div style="font-family:var(--font-mono)">{{ $rechnung->datum->format('d.m.Y') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Kunde --}}
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-divider)">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Kunde</div>
                    <div style="font-weight:500">{{ $rechnung->kunde_firma ?? $rechnung->kunde_name ?? '—' }}</div>
                </div>

                {{-- Fälligkeit --}}
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-divider)">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Fälligkeit</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Fälligkeitsdatum</div>
                            <div style="font-family:var(--font-mono)">{{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}</div>
                        </div>
                        @if($rechnung->leistungsdatum)
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Leistungsdatum</div>
                                <div style="font-family:var(--font-mono)">{{ $rechnung->leistungsdatum->format('d.m.Y') }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Zusatzinfo --}}
                @if($rechnung->intern_ref)
                    <div style="padding:var(--space-4) var(--space-5)">
                        <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Zusatzinfo</div>
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Interne Ref.</div>
                            <div style="font-weight:500">{{ $rechnung->intern_ref }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Status --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Status</div>
            </div>
            <div class="card-body">
                <div style="margin-bottom:var(--space-4)">
                    <span class="status-badge {{ $rechnung->statusBadgeClass() }}" style="font-size:var(--text-sm);padding:var(--space-1) var(--space-3)">
                        {{ $rechnung->statusLabel() }}
                    </span>
                    @if($rechnung->ist_storno && $rechnung->stornoVon)
                        <div style="margin-top:var(--space-2);padding:var(--space-2) var(--space-3);background:var(--color-error-highlight);border-radius:var(--radius-md);font-size:var(--text-xs)">
                            <i data-lucide="file-minus" width="12" height="12" style="display:inline"></i>
                            Storno zu: <a href="{{ route('rechnungen.show', $rechnung->stornoVon) }}" style="color:var(--color-primary);text-decoration:underline">{{ $rechnung->stornoVon->rechnungsnummer }}</a>
                        </div>
                    @endif
                    @if($rechnung->isOverdue() || $rechnung->isUeberfaellig())
                        <div style="font-size:var(--text-xs);color:var(--color-error);margin-top:var(--space-1)">
                            {{ $rechnung->daysOverdue() }} Tage überfällig
                        </div>
                    @endif
                    @if($rechnung->bezahldatum)
                        <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:var(--space-1)">
                            Bezahlt am {{ $rechnung->bezahldatum->format('d.m.Y') }}
                        </div>
                    @endif
                    @if($rechnung->mahnungen_count > 0)
                        <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:var(--space-1)">
                            {{ $rechnung->mahnungen_count }} Mahnung(en) versendet
                        </div>
                    @endif
                    @if($rechnung->isStorniert() && $rechnung->stornoBeleg()->count() > 0)
                        @php $storno = $rechnung->stornoBeleg()->first(); @endphp
                        <div style="margin-top:var(--space-3);padding:var(--space-3);background:var(--color-error-highlight);border-radius:var(--radius-md);font-size:var(--text-xs)">
                            <div style="font-weight:600;color:var(--color-error);margin-bottom:4px">
                                <i data-lucide="file-text" width="12" height="12" style="display:inline"></i> Stornobeleg
                            </div>
                            <div>{{ $storno->rechnungsnummer }}</div>
                            <div style="margin-top:4px">
                                <a href="{{ route('rechnungen.storno-pdf-view', $storno) }}" target="_blank" style="color:var(--color-primary);text-decoration:underline">
                                    <i data-lucide="eye" width="10" height="10" style="display:inline"></i> Ansehen
                                </a>
                                ·
                                <a href="{{ route('rechnungen.storno-pdf', $storno) }}" style="color:var(--color-primary);text-decoration:underline">
                                    <i data-lucide="download" width="10" height="10" style="display:inline"></i> Download
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                @if($rechnung->isDraft())
                    <form method="POST" action="{{ route('rechnungen.status', $rechnung) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="bestaetigt">
                        <button type="submit" class="btn btn-sm btn-secondary" style="width:100%">
                            <i data-lucide="check" width="14" height="14"></i> Bestätigen
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Aktionen --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Aktionen</div>
            </div>
            <div class="card-body">
                <div>
                    <a href="{{ route('rechnungen.pdf', $rechnung) }}" class="btn btn-sm btn-primary" style="width:100%;text-decoration:none;margin-bottom:4px">
                        <i data-lucide="download" width="14" height="14"></i> Download PDF
                    </a>

                    @if($rechnung->isDraft())
                        <a href="{{ route('rechnungen.edit', $rechnung) }}" class="btn btn-sm btn-secondary" style="width:100%;text-decoration:none;margin-bottom:4px">
                            <i data-lucide="pencil" width="14" height="14"></i> Bearbeiten
                        </a>
                    @endif

                    @if($rechnung->isVersendet() || $rechnung->isUeberfaellig())
                        <form method="POST" action="{{ route('rechnungen.status', $rechnung) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="bezahlt">
                            <button type="submit" class="btn btn-sm btn-primary" style="width:100%;margin-bottom:4px">
                                <i data-lucide="check-circle" width="14" height="14"></i> Als bezahlt markieren
                            </button>
                        </form>
                    @endif

                    @if($rechnung->isBestaetigt() && $rechnung->kunde_email)
                        <form method="POST" action="{{ route('rechnungen.email', $rechnung) }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $rechnung->kunde_email }}">
                            <button type="submit" class="btn btn-sm btn-primary" style="width:100%;margin-bottom:4px">
                                <i data-lucide="mail" width="14" height="14"></i> Rechnung per E-Mail senden
                            </button>
                        </form>
                    @endif

                    @if(($rechnung->isVersendet() || $rechnung->isUeberfaellig()) && $rechnung->kunde_email)
                        <form method="POST" action="{{ route('rechnungen.mahnung', $rechnung) }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $rechnung->kunde_email }}">
                            <button type="submit" class="btn btn-sm btn-danger" style="width:100%;margin-bottom:4px" onclick="return confirm('Mahnung wirklich versenden?')">
                                <i data-lucide="alert-triangle" width="14" height="14"></i> Mahnung versenden
                            </button>
                        </form>
                    @endif

                    @if(($rechnung->isBestaetigt() || $rechnung->isVersendet() || $rechnung->isUeberfaellig()) && !$rechnung->ist_storno && $rechnung->stornoBeleg()->count() === 0)
                        <button type="button" class="btn btn-sm btn-danger" style="width:100%;margin-bottom:4px" onclick="document.getElementById('modalStornieren').classList.add('open')">
                            <i data-lucide="x-circle" width="14" height="14"></i> Stornieren
                        </button>
                    @endif

                    <form method="POST" action="{{ route('rechnungen.duplicate', $rechnung) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary" style="width:100%;margin-bottom:4px">
                            <i data-lucide="copy" width="14" height="14"></i> Duplizieren
                        </button>
                    </form>

                    @if($rechnung->isDraft())
                        <form method="POST" action="{{ route('rechnungen.destroy', $rechnung) }}" onsubmit="return confirm('Rechnung wirklich löschen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" style="width:100%">
                                <i data-lucide="trash-2" width="14" height="14"></i> Löschen
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div>
        {{-- PDF-Vorschau --}}
        <div class="card" style="padding:0;overflow:hidden">
            <iframe
                src="{{ route('rechnungen.pdf-view', $rechnung) }}"
                style="width:100%;height:calc(100vh - 160px);border:none"
                title="PDF-Vorschau {{ $rechnung->rechnungsnummer }}"
            ></iframe>
        </div>
    </div>
</div>

{{-- Modal: Rechnung stornieren --}}
<div class="modal-overlay" id="modalStornieren" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal" style="max-width:28rem">
        <div style="text-align:center;padding:var(--space-6) var(--space-6) var(--space-4)">
            <div style="width:48px;height:48px;border-radius:50%;background:var(--color-error-bg, #fef2f2);display:inline-flex;align-items:center;justify-content:center;margin-bottom:var(--space-4)">
                <i data-lucide="alert-triangle" width="24" height="24" style="color:var(--color-error)"></i>
            </div>
            <div style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-2)">Rechnung stornieren?</div>
            <div style="font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.6">
                Möchtest du die Rechnung <strong>{{ $rechnung->rechnungsnummer }}</strong> wirklich stornieren?<br>
                Es wird automatisch ein Stornobeleg erstellt.
            </div>
        </div>
        <div style="display:flex;gap:var(--space-3);padding:0 var(--space-6) var(--space-6)">
            <button type="button" class="btn btn-secondary" style="flex:1" onclick="document.getElementById('modalStornieren').classList.remove('open')">
                Abbrechen
            </button>
            <form method="POST" action="{{ route('rechnungen.status', $rechnung) }}" style="flex:1">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="storniert">
                <button type="submit" class="btn btn-danger" style="width:100%">
                    <i data-lucide="x-circle" width="14" height="14"></i> Ja, stornieren
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
