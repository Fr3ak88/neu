@extends('layouts.app')

@section('title', 'Bestände')

@section('content')
<div class="page-header">
    <div class="page-title">Bestände</div>
    <div class="page-subtitle">Aktuelle Lagerbestände einsehen</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <form method="GET" action="{{ route('wms.products.index') }}" style="display:flex;gap:var(--space-2)">
            <input type="text" name="search" class="inp" placeholder="SKU, Name oder EAN suchen…" value="{{ request('search') }}" style="width:280px">
            <button type="submit" class="btn btn-secondary btn-sm">Suchen</button>
        </form>
    </div>
</div>

<div class="card">
    @if($products->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine Bestände vorhanden.
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="article-table">
                <thead>
                    <tr>
                        <th>SKU</th><th>Name</th><th>EAN</th><th>Bestand</th><th>Preis</th><th>Letzter Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td><span class="article-sku">{{ $product->sku }}</span></td>
                        <td>{{ $product->name }}</td>
                        <td><small class="article-sku">{{ $product->ean ?: '—' }}</small></td>
                        <td style="font-family:var(--font-mono)">
                            @if($product->quantity <= 0)
                                <span style="color:var(--color-error)">{{ $product->quantity }}</span>
                            @elseif($product->quantity <= 5)
                                <span style="color:var(--color-warning)">{{ $product->quantity }}</span>
                            @else
                                {{ $product->quantity }}
                            @endif
                        </td>
                        <td style="font-family:var(--font-mono)">{{ $product->price ? number_format($product->price, 2, ',', '.') . ' €' : '—' }}</td>
                        <td><small class="article-sku">{{ $product->last_synced_at?->format('d.m.Y H:i') ?: 'Nie' }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:var(--space-4)">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
