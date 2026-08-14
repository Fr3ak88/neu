@extends('layouts.app')

@section('title', 'Amazon Inbound Plans')

@section('content')
<div style="padding:var(--space-8) 0">
    <div style="max-width:80rem;margin:0 auto;padding:0 var(--space-4)">
        <div class="card" style="padding:var(--space-6)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-6)">
                <h3 style="font-size:1.125rem;font-weight:600;color:var(--text-primary)">
                    Pläne für Marketplace: {{ $marketplaceId }}
                </h3>
            </div>

            @if(count($plans) > 0)
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:0.875rem">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Plan-ID</th>
                                <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Status</th>
                                <th style="text-align:left;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Erstellt</th>
                                <th style="text-align:center;padding:var(--space-3);border-bottom:2px solid var(--border);color:var(--text-secondary);font-weight:500">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                                <tr style="border-bottom:1px solid var(--border)">
                                    <td style="padding:var(--space-3);color:var(--text-primary)">
                                        {{ $plan['inboundPlanId'] ?? '–' }}
                                    </td>
                                    <td style="padding:var(--space-3)">
                                        @php
                                            $status = $plan['status'] ?? 'UNKNOWN';
                                            $statusColor = match(true) {
                                                $status === 'ACTIVE' => 'var(--success)',
                                                $status === 'CANCELLED' => 'var(--danger)',
                                                default => 'var(--text-secondary)',
                                            };
                                        @endphp
                                        <span style="font-size:0.75rem;font-weight:500;color:{{ $statusColor }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td style="padding:var(--space-3);color:var(--text-secondary)">
                                        {{ $plan['creationTime'] ?? '–' }}
                                    </td>
                                    <td style="padding:var(--space-3);text-align:center">
                                        <a href="{{ route('fba-shipments.plan-detail', $plan['inboundPlanId']) }}"
                                           class="btn btn-secondary" style="font-size:0.75rem">
                                            <i data-lucide="eye" width="14" height="14"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align:center;padding:var(--space-12) 0;color:var(--text-secondary)">
                    <i data-lucide="package" width="48" height="48" style="margin:0 auto var(--space-3);opacity:0.3"></i>
                    <p style="font-size:1rem;font-weight:500;color:var(--text-primary)">Keine Pläne gefunden</p>
                    <p style="font-size:0.875rem;margin-top:var(--space-2)">Für dieses Marketplace gibt es noch keine Inbound Plans.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
