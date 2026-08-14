@extends('layouts.app')

@section('title', $auftrag->auftragsnummer . ' – ' . $auftrag->bezeichnung)

@section('content')
<div class="page-header">
    <div>
        <div class="page-title">{{ $auftrag->auftragsnummer }}</div>
        <div class="page-subtitle">
            {{ $auftrag->bezeichnung }} ·
            @if($auftrag->isEinmalig())
                <span class="status-badge status-info" style="margin-right:var(--space-2)">Einmalig</span>
            @else
                <span class="status-badge status-ok" style="margin-right:var(--space-2)">Wiederkehrend</span>
                {{ $auftrag->intervallLabel() }} ·
            @endif
            Erstellt am {{ $auftrag->created_at?->format('d.m.Y') ?? '–' }}
        </div>
    </div>
    <div style="display:flex;gap:var(--space-3)">
        <a href="{{ route('auftraege.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left" width="16" height="16"></i> Zurück
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 3fr;gap:var(--space-6);align-items:start">
    <div>
        {{-- Erstellte Rechnungen --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i data-lucide="file-text" width="16" height="16"></i> Erstellte Rechnungen ({{ $auftrag->rechnungen->count() }})</div>
            </div>
            @if($auftrag->rechnungen->isEmpty())
                <div style="text-align:center;padding:var(--space-8) 0;color:var(--color-text-faint);font-size:var(--text-sm)">
                    @if($auftrag->isEinmalig())
                        Noch keine Rechnung erstellt.
                    @else
                        Noch keine Rechnungen erstellt.
                    @endif
                </div>
            @else
                <div class="card-body" style="padding:0">
                    <table class="article-table">
                        <thead>
                            <tr>
                                <th>Nr.</th>
                                <th>Datum</th>
                                <th>Fällig</th>
                                <th style="text-align:right">Betrag</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($auftrag->rechnungen->sortByDesc('datum') as $r)
                                <tr>
                                    <td>
                                        <a href="{{ route('rechnungen.show', $r) }}" style="color:var(--color-primary);font-family:var(--font-mono);font-weight:500;text-decoration:none">
                                            {{ $r->rechnungsnummer }}
                                        </a>
                                    </td>
                                    <td style="font-size:var(--text-sm)">{{ $r->datum->format('d.m.Y') }}</td>
                                    <td style="font-size:var(--text-sm)">{{ $r->faelligkeitsdatum->format('d.m.Y') }}</td>
                                    <td style="text-align:right;font-family:var(--font-mono);font-weight:500">{{ number_format($r->bruttobetrag, 2, ',', '.') }} €</td>
                                    <td><span class="status-badge {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Status --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Status</div>
            </div>
            <div class="card-body">
                <div style="margin-bottom:var(--space-4)">
                    <span class="status-badge {{ $auftrag->statusClass() }}">{{ $auftrag->statusLabel() }}</span>
                </div>

                @if(!$auftrag->isEinmalig() && $auftrag->isFaelig())
                    <div class="alert alert-info" style="margin-bottom:var(--space-4)">
                        <div class="alert-text">Nächste Rechnung ist fällig.</div>
                    </div>
                @endif

                @if(!$auftrag->isEinmalig() && !$auftrag->isAbgelaufen())
                    <form method="POST" action="{{ route('auftraege.toggle', $auftrag) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $auftrag->aktiv ? 'btn-danger' : 'btn-primary' }}" style="width:100%">
                            {{ $auftrag->aktiv ? 'Pausieren' : 'Aktivieren' }}
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
                    <a href="{{ route('auftraege.pdf', $auftrag) }}" class="btn btn-sm btn-primary" style="width:100%;text-decoration:none;margin-bottom:4px" download>
                        <i data-lucide="download" width="14" height="14"></i> Download PDF
                    </a>

                    @if($auftrag->kunde_email)
                        <form method="POST" action="{{ route('auftraege.email', $auftrag) }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $auftrag->kunde_email }}">
                            <button type="submit" class="btn btn-sm btn-primary" style="width:100%;margin-bottom:4px">
                                <i data-lucide="mail" width="14" height="14"></i> Auftrag per E-Mail senden
                            </button>
                        </form>
                    @endif

                    @if($auftrag->aktiv && !$auftrag->isAbgelaufen())
                        @if($auftrag->rechnungen->isEmpty())
                            <form method="POST" action="{{ route('auftraege.erstelle-jetzt', $auftrag) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" style="width:100%;margin-bottom:4px">
                                    <i data-lucide="file-plus" width="14" height="14"></i> Rechnung erstellen
                                </button>
                            </form>
                        @else
                            <button type="button" class="btn btn-sm btn-primary" disabled style="width:100%;margin-bottom:4px;opacity:.5;cursor:not-allowed">
                                <i data-lucide="file-check" width="14" height="14"></i> Rechnung bereits erstellt
                            </button>
                        @endif
                    @endif

                    <a href="{{ route('auftraege.edit', $auftrag) }}" class="btn btn-sm btn-secondary" style="width:100%;text-decoration:none;margin-bottom:4px">
                        <i data-lucide="pencil" width="14" height="14"></i> Bearbeiten
                    </a>

                    <button type="button" class="btn btn-sm btn-danger" style="width:100%" onclick="document.getElementById('deleteModal').style.display='flex'">
                        <i data-lucide="trash-2" width="14" height="14"></i> Löschen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div>
        {{-- Positionen --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i data-lucide="list" width="16" height="16"></i> Positionen</div>
            </div>
            <div class="card-body">
                <table class="article-table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Beschreibung</th>
                            <th style="width:70px">Menge</th>
                            <th style="width:60px">Einheit</th>
                            <th style="width:90px">Einzelpreis</th>
                            <th style="width:70px">MwSt</th>
                            @if($auftrag->positions->contains(fn($p) => $p->rabatt > 0))
                                <th style="width:70px">Rabatt</th>
                            @endif
                            <th style="width:110px">Netto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auftrag->positions as $pos)
                            <tr>
                                <td style="font-family:var(--font-mono);color:var(--color-text-faint)">{{ $pos->position }}</td>
                                <td>{{ $pos->beschreibung }}</td>
                                <td style="text-align:right;font-family:var(--font-mono)">{{ number_format($pos->menge, 2, ',', '.') }}</td>
                                <td>{{ $pos->einheit }}</td>
                                <td style="text-align:right;font-family:var(--font-mono)">{{ number_format($pos->einzelpreis, 2, ',', '.') }} €</td>
                                <td style="text-align:right;font-family:var(--font-mono);font-size:var(--text-sm)">{{ number_format($pos->steuersatz, 0, ',', '.') }}%</td>
                                @if($auftrag->positions->contains(fn($p) => $p->rabatt > 0))
                                    <td style="text-align:right;font-family:var(--font-mono);font-size:var(--text-sm)">{{ $pos->rabatt > 0 ? number_format($pos->rabatt, 0, ',', '.') . '%' : '—' }}</td>
                                @endif
                                <td style="text-align:right;font-family:var(--font-mono);font-weight:500">{{ number_format($pos->menge * $pos->einzelpreis * (1 - $pos->rabatt / 100), 2, ',', '.') }} €</td>
                            </tr>
                            @if($pos->notizen)
                                <tr>
                                    <td colspan="{{ $auftrag->positions->contains(fn($p) => $p->rabatt > 0) ? 8 : 7 }}" style="padding:0 var(--space-2) var(--space-2);font-size:var(--text-sm);color:var(--color-text-muted);font-style:italic">
                                        <i data-lucide="file-text" width="12" height="12" style="vertical-align:-1px"></i> {{ $pos->notizen }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Details --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i data-lucide="info" width="16" height="16"></i> Details</div>
            </div>
            <div class="card-body" style="padding:0">
                {{-- Auftrag --}}
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-divider)">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Auftrag</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Nr.</div>
                            <div style="font-family:var(--font-mono);font-weight:500">{{ $auftrag->auftragsnummer }}</div>
                        </div>
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Kunde</div>
                            <div style="font-weight:500">{{ $auftrag->kunde_firma ?? $auftrag->kunde_name ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Typ & Intervall --}}
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-divider)">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Konfiguration</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Typ</div>
                            <div>
                                @if($auftrag->isEinmalig())
                                    <span class="status-badge status-info">Einmalig</span>
                                @else
                                    <span class="status-badge status-ok">Wiederkehrend</span>
                                @endif
                            </div>
                        </div>
                        @if($auftrag->isWiederkehrend())
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Intervall</div>
                                <div style="font-weight:500">{{ $auftrag->intervallLabel() }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Zeitraum --}}
                <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-divider)">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Zeitraum</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                        <div>
                            <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Start</div>
                            <div style="font-family:var(--font-mono)">{{ $auftrag->startdatum->format('d.m.Y') }}</div>
                        </div>
                        @if($auftrag->enddatum)
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Ende</div>
                                <div style="font-family:var(--font-mono)">{{ $auftrag->enddatum->format('d.m.Y') }}</div>
                            </div>
                        @endif
                    </div>
                    @if($auftrag->isWiederkehrend())
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-top:var(--space-3)">
                            <div>
                                <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Nächste Erstellung</div>
                                <div style="font-family:var(--font-mono);font-weight:500;color:var(--color-primary)">{{ $auftrag->naechste_erstellung->format('d.m.Y') }}</div>
                            </div>
                            @if($auftrag->letzte_erstellung)
                                <div>
                                    <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Letzte Erstellung</div>
                                    <div style="font-family:var(--font-mono)">{{ $auftrag->letzte_erstellung->format('d.m.Y') }}</div>
                                </div>
                            @endif
                        </div>
                    @endif
                    <div style="margin-top:var(--space-3)">
                        <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:2px">Erstellungen</div>
                        <div style="font-family:var(--font-mono);font-weight:600;font-size:var(--text-lg)">{{ $auftrag->erstellt_count }}×</div>
                    </div>
                </div>

                {{-- Beträge --}}
                <div style="padding:var(--space-4) var(--space-5)">
                    <div style="font-size:var(--text-xs);font-weight:600;color:var(--color-text-faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--space-3)">Beträge</div>
                    <div style="display:flex;flex-direction:column;gap:var(--space-2)">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Nettobetrag</span>
                            <span style="font-family:var(--font-mono);font-weight:500">{{ number_format($auftrag->nettobetrag(), 2, ',', '.') }} €</span>
                        </div>
                        @foreach($auftrag->steuerAufschluesselung() as $gruppe)
                            <div style="display:flex;justify-content:space-between;align-items:center;padding-left:var(--space-3)">
                                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $gruppe['satz'] }}% MwSt</span>
                                <span style="font-family:var(--font-mono);font-weight:500">{{ number_format($gruppe['steuer'], 2, ',', '.') }} €</span>
                            </div>
                        @endforeach
                        <div style="height:1px;background:var(--color-divider);margin:var(--space-1) 0"></div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:var(--text-sm);font-weight:600">Bruttobetrag</span>
                            <span style="font-family:var(--font-mono);font-weight:700;font-size:var(--text-base);color:var(--color-primary)">{{ number_format($auftrag->bruttobetrag(), 2, ',', '.') }} €</span>
                        </div>
                        @if($auftrag->isWiederkehrend())
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-1)">
                                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">≈ Monatsbetrag</span>
                                <span style="font-family:var(--font-mono);font-weight:600;color:var(--color-primary)">{{ number_format($auftrag->monatlicherBetrag(), 2, ',', '.') }} €</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--color-bg);border-radius:12px;padding:var(--space-8);max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4)">
            <div style="width:40px;height:40px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i data-lucide="alert-triangle" width="20" height="20" style="color:#DC2626"></i>
            </div>
            <h3 style="margin:0;font-size:var(--text-lg);font-weight:600">Auftrag löschen?</h3>
        </div>
        <p style="margin:0 0 var(--space-6);color:var(--color-text-secondary);line-height:1.6">
            Möchtest du den Auftrag <strong>{{ $auftrag->auftragsnummer }}</strong> wirklich unwiderruflich löschen? Diese Aktion kann nicht rückgängig gemacht werden.
        </p>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').style.display='none'">Abbrechen</button>
            <form method="POST" action="{{ route('auftraege.destroy', $auftrag) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i data-lucide="trash-2" width="14" height="14"></i> Endgültig löschen
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
