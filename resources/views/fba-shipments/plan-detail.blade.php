@extends('layouts.app')
@section('title', 'Plan Detail')
@section('content')
    <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-6)">
        <a href="{{ route('fba-shipments.plans') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="arrow-left" width="16" height="16"></i>
        </a>
        <div>
            <h2 class="font-semibold text-xl" style="color:var(--text-primary);line-height:1.2">
                Plan: {{ $plan['inboundPlanId'] ?? '–' }}
            </h2>
            <p class="text-sm" style="color:var(--text-secondary);margin-top:2px">Amazon Inbound Plan Details</p>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card" style="padding:var(--space-6);margin-bottom:var(--space-6)">
                <h3 style="font-size:1.125rem;font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4)">Plan-Informationen</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:var(--space-4)">
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:2px">Plan-ID</div>
                        <div style="font-size:0.875rem;color:var(--text-primary);font-weight:500">{{ $plan['inboundPlanId'] ?? '–' }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:2px">Status</div>
                        <div style="font-size:0.875rem;color:var(--text-primary);font-weight:500">{{ $plan['status'] ?? '–' }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:2px">Marketplace</div>
                        <div style="font-size:0.875rem;color:var(--text-primary);font-weight:500">{{ $plan['marketplaceId'] ?? '–' }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:2px">Erstellt</div>
                        <div style="font-size:0.875rem;color:var(--text-primary);font-weight:500">{{ $plan['creationTime'] ?? '–' }}</div>
                    </div>
                </div>
            </div>

            @if(!empty($plan['shipments']))
                <div class="card" style="padding:var(--space-6)">
                    <h3 style="font-size:1.125rem;font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4)">Shipments</h3>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:0.875rem">
                            <thead>
                                <tr>
                                    <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Shipment-ID</th>
                                    <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Name</th>
                                    <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">FC</th>
                                    <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($plan['shipments'] as $shipment)
                                    <tr style="border-bottom:1px solid var(--border)">
                                        <td style="padding:var(--space-3);color:var(--text-primary);font-family:monospace;font-size:0.8125rem">
                                            {{ $shipment['shipmentId'] ?? '–' }}
                                        </td>
                                        <td style="padding:var(--space-3);color:var(--text-primary)">{{ $shipment['name'] ?? '–' }}</td>
                                        <td style="padding:var(--space-3);color:var(--text-secondary)">{{ $shipment['destinationFC'] ?? '–' }}</td>
                                        <td style="padding:var(--space-3)">
                                            @php $s = $shipment['status'] ?? 'UNKNOWN'; @endphp
                                            <span style="font-size:0.75rem;font-weight:500;color:{{ $s === 'SHIPPED' ? 'var(--success)' : 'var(--text-secondary)' }}">
                                                {{ $s }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>lucide.createIcons();</script>
@endsection
