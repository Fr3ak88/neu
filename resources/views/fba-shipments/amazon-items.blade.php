@extends('layouts.app')
@section('title', 'Amazon Items')
@section('content')
    <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-6)">
        <a href="{{ route('fba-shipments.show', $shipment) }}" class="btn btn-ghost btn-sm">
            <i data-lucide="arrow-left" width="16" height="16"></i>
        </a>
        <div>
            <h2 class="font-semibold text-xl" style="color:var(--text-primary);line-height:1.2">Amazon Shipment Items</h2>
            <p class="text-sm" style="color:var(--text-secondary);margin-top:2px">Artikel aus der Amazon SP-API</p>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card" style="padding:var(--space-6)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-6)">
                    <h3 style="font-size:1.125rem;font-weight:600;color:var(--text-primary)">
                        Artikel für {{ $shipment->title }}
                    </h3>
                </div>

                @if(count($items) > 0)
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:0.875rem">
                            <thead>
                                <tr>
                                    <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">SKU</th>
                                    <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">FNSKU</th>
                                    <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">ASIN</th>
                                    <th style="text-align:right;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Menge</th>
                                    <th style="text-align:center;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Prep</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr style="border-bottom:1px solid var(--border)">
                                        <td style="padding:var(--space-3);color:var(--text-primary);font-family:monospace;font-size:0.8125rem">
                                            {{ $item['sellerSku'] ?? '–' }}
                                        </td>
                                        <td style="padding:var(--space-3);color:var(--text-primary);font-family:monospace;font-size:0.8125rem">
                                            {{ $item['fnSku'] ?? '–' }}
                                        </td>
                                        <td style="padding:var(--space-3);color:var(--text-secondary)">{{ $item['asin'] ?? '–' }}</td>
                                        <td style="padding:var(--space-3);text-align:right;color:var(--text-primary);font-weight:500">
                                            {{ $item['quantityShipped'] ?? $item['quantityInCase'] ?? '–' }}
                                        </td>
                                        <td style="padding:var(--space-3);text-align:center">
                                            @if(!empty($item['prepDetails']))
                                                <span style="font-size:0.75rem;color:var(--accent)">✓</span>
                                            @else
                                                <span style="font-size:0.75rem;color:var(--text-secondary)">–</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align:center;padding:var(--space-12) 0;color:var(--text-secondary)">
                        <i data-lucide="package" width="48" height="48" style="margin:0 auto var(--space-3);opacity:0.3"></i>
                        <p style="font-size:1rem;font-weight:500;color:var(--text-primary)">Keine Artikel gefunden</p>
                        <p style="font-size:0.875rem;margin-top:var(--space-2)">Für dieses Shipment gibt es noch keine Artikel.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
@endsection
