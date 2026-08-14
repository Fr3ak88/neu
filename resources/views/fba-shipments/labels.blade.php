@extends('layouts.app')
@section('title', 'Labels')
@section('content')
    <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-6)">
        <a href="{{ route('fba-shipments.show', $shipment) }}" class="btn btn-ghost btn-sm">
            <i data-lucide="arrow-left" width="16" height="16"></i>
        </a>
        <div>
            <h2 class="font-semibold text-xl" style="color:var(--text-primary);line-height:1.2">Shipping Labels</h2>
            <p class="text-sm" style="color:var(--text-secondary);margin-top:2px">{{ $shipment->title }}</p>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('error'))
                <div style="background:var(--danger-bg);border:1px solid var(--danger-border);color:var(--danger-text);padding:var(--space-4);border-radius:var(--radius);margin-bottom:var(--space-6);font-size:0.875rem">
                    {{ session('error') }}
                </div>
            @endif

            <div class="card" style="padding:var(--space-6);margin-bottom:var(--space-6)">
                <h3 style="font-size:1.125rem;font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4)">Label-Übersicht</h3>
                <p style="font-size:0.875rem;color:var(--text-secondary);margin-bottom:var(--space-4)">
                    Die folgenden Labels wurden von Amazon generiert. Lade sie herunter und drucke sie aus.
                </p>

                @if(count($labels) > 0)
                    <div style="display:grid;gap:var(--space-4)">
                        @foreach($labels as $label)
                            <div style="border:1px solid var(--border);border-radius:var(--radius);padding:var(--space-4)">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3)">
                                    <div>
                                        <span style="font-weight:600;color:var(--text-primary)">Shipment: </span>
                                        <span style="font-family:monospace;font-size:0.875rem;color:var(--text-primary)">{{ $label['shipment_id'] ?? '–' }}</span>
                                    </div>
                                </div>

                                @if(!empty($label['labels']))
                                    <div style="display:grid;gap:var(--space-2)">
                                        @foreach($label['labels'] as $l)
                                            @if(is_array($l))
                                                {{-- Label als Link --}}
                                                @if(!empty($l['downloadUrl']))
                                                    <a href="{{ $l['downloadUrl'] }}" target="_blank" rel="noopener"
                                                       style="display:flex;align-items:center;gap:var(--space-2);padding:var(--space-2) var(--space-3);border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:var(--text-primary);font-size:0.875rem;transition:background 0.15s"
                                                       onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
                                                        <i data-lucide="download" width="14" height="14" style="color:var(--accent)"></i>
                                                        {{ $l['labelType'] ?? 'Label' }}
                                                    </a>
                                                @else
                                                    <div style="padding:var(--space-2) var(--space-3);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:0.8125rem;color:var(--text-secondary)">
                                                        {{ $l['labelType'] ?? 'Label' }} — {{ json_encode($l) }}
                                                    </div>
                                                @endif
                                            @else
                                                <div style="padding:var(--space-2) var(--space-3);font-size:0.8125rem;color:var(--text-secondary)">{{ $l }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div style="font-size:0.8125rem;color:var(--text-secondary);font-style:italic">
                                        Labels werden generiert… Bitte später erneut prüfen.
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align:center;padding:var(--space-8) 0;color:var(--text-secondary)">
                        <i data-lucide="printer" width="48" height="48" style="margin:0 auto var(--space-3);opacity:0.3"></i>
                        <p style="font-size:1rem;font-weight:500;color:var(--text-primary)">Keine Labels vorhanden</p>
                        <p style="font-size:0.875rem;margin-top:var(--space-2)">Labels werden erst nach Registrierung bei Amazon generiert.</p>
                    </div>
                @endif
            </div>

            <div style="display:flex;gap:var(--space-3)">
                <button onclick="window.print()" class="btn btn-primary">
                    <i data-lucide="printer" width="16" height="16"></i> Drucken
                </button>
                <a href="{{ route('fba-shipments.show', $shipment) }}" class="btn btn-secondary">Zurück</a>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
@endsection
