@extends('layouts.app')

@section('title', 'Retouren')

@section('content')
<div class="page-header">
    <div class="page-title">Retouren</div>
    <div class="page-subtitle">Retouren von Storlogix verwalten</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <form method="GET" action="{{ route('wms.returns.index') }}" style="display:flex;gap:var(--space-2)">
            <input type="text" name="search" class="inp" placeholder="Retourennr., Storlogix-ID oder Grund…" value="{{ request('search') }}" style="width:300px">
            <select name="status" class="inp">
                <option value="">Alle Status</option>
                <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Eingegangen</option>
                <option value="inspected" {{ request('status') === 'inspected' ? 'selected' : '' }}>Geprüft</option>
                <option value="restocked" {{ request('status') === 'restocked' ? 'selected' : '' }}>Eingelagert</option>
                <option value="disposed" {{ request('status') === 'disposed' ? 'selected' : '' }}>Entorgt</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Suchen</button>
        </form>
    </div>
</div>

<div class="card">
    @if($returns->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine Retouren vorhanden.
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="article-table">
                <thead>
                    <tr>
                        <th>Retourennr.</th><th>Bestellung</th><th>Grund</th><th>Menge</th><th>Status</th><th>Eingang</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returns as $return)
                    <tr>
                        <td>
                            <a href="{{ route('wms.returns.show', $return) }}" style="color:var(--color-text);text-decoration:none">
                                <b>{{ $return->return_number ?: $return->storlogix_return_id ?: '—' }}</b>
                            </a>
                        </td>
                        <td>{{ $return->order?->order_number ?: '—' }}</td>
                        <td><small>{{ Str::limit($return->reason, 40) }}</small></td>
                        <td style="font-family:var(--font-mono)">{{ $return->quantity }}</td>
                        <td>
                            <span class="status-badge {{ $return->statusClass() }}">{{ $return->statusLabel() }}</span>
                        </td>
                        <td><small class="article-sku">{{ $return->received_at?->format('d.m.Y') }}</small></td>
                        <td>
                            <a href="{{ route('wms.returns.show', $return) }}" class="btn btn-ghost btn-sm">Details</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:var(--space-4)">
            {{ $returns->links() }}
        </div>
    @endif
</div>
@endsection
