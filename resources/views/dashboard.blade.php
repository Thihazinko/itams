@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    $now = \Carbon\Carbon::now();
    $greeting = $now->hour < 12 ? 'Good morning' : ($now->hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $greeting }}, {{ explode(' ', $user->name)[0] }}</h1>
        <div class="page-subtitle">{{ $now->format('l, F j, Y') }} &middot; Here's what's happening across your IT assets.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($user->canAccess('pc_assets'))
            <a href="{{ route('pc-assets.index') }}" class="quick-action"><i class="bi bi-pc-display"></i> PCs</a>
        @endif
        @if($user->canAccess('devices'))
            <a href="{{ route('devices.index') }}" class="quick-action"><i class="bi bi-hdd-network"></i> Devices</a>
        @endif
        @if($user->canAccess('subscriptions'))
            <a href="{{ route('subscriptions.index') }}" class="quick-action"><i class="bi bi-calendar-event"></i> Subscriptions</a>
        @endif
        @if($user->canAccess('licenses_contracts'))
            <a href="{{ route('licenses-contracts.index') }}" class="quick-action"><i class="bi bi-file-earmark-text"></i> Licenses</a>
        @endif
        @if($user->canAccess('email_master'))
            <a href="{{ route('email-master.index') }}" class="quick-action"><i class="bi bi-envelope-at"></i> Email</a>
        @endif
        @if($user->canAccess('financial_management'))
            <a href="{{ route('financial-pos.index') }}" class="quick-action"><i class="bi bi-cash-coin"></i> Finance</a>
        @endif
        @if($user->canAccess('gcp_costs'))
            <a href="{{ route('gcp-costs.index') }}" class="quick-action"><i class="bi bi-cloud"></i> GCP Costs</a>
        @endif
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-3">
    <div class="col">
        <div class="card kpi-card kpi-blue h-100">
            <div class="card-body">
                <div class="kpi-label">Total PC Assets</div>
                <div class="kpi-value">{{ number_format($stats['total_assets']) }}</div>
                <div class="kpi-foot">{{ $stats['active_assets'] }} active &middot; {{ $stats['free_assets'] }} free</div>
                <i class="bi bi-pc-display kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card kpi-card kpi-green h-100">
            <div class="card-body">
                <div class="kpi-label">Devices Tracked</div>
                <div class="kpi-value">{{ number_format($stats['total_devices']) }}</div>
                <div class="kpi-foot">{{ number_format($stats['devices_qty']) }} units &middot; {{ $stats['active_devices'] }} active</div>
                <i class="bi bi-hdd-network kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card kpi-card kpi-purple h-100">
            <div class="card-body">
                <div class="kpi-label">Active Subscriptions</div>
                <div class="kpi-value">{{ number_format($stats['active_subscriptions']) }}</div>
                <div class="kpi-foot">of {{ $stats['total_subscriptions'] }} &middot; {{ $stats['active_licenses'] }} licenses</div>
                <i class="bi bi-calendar-event kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card kpi-card kpi-teal h-100">
            <div class="card-body">
                <div class="kpi-label">Email Accounts</div>
                <div class="kpi-value">{{ number_format($stats['total_email_accounts']) }}</div>
                <div class="kpi-foot">{{ $stats['gmail_accounts'] }} Gmail &middot; {{ $stats['email_accounts'] }} Email &middot; {{ $stats['email_aliases'] }} alias</div>
                <i class="bi bi-envelope-at kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card kpi-card kpi-indigo h-100">
            <div class="card-body">
                <div class="kpi-label">Purchase Orders</div>
                <div class="kpi-value">{{ number_format($stats['total_pos']) }}</div>
                <div class="kpi-foot">{{ number_format($stats['total_receipts']) }} receipts &middot; {{ $stats['pos_this_month'] }} this month</div>
                <i class="bi bi-cash-coin kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card kpi-card kpi-cyan h-100">
            <div class="card-body">
                <div class="kpi-label">GCP Cloud Cost</div>
                <div class="kpi-value">{{ $stats['gcp_latest_jpy'] ? '¥' . number_format($stats['gcp_latest_jpy']) : '—' }}</div>
                <div class="kpi-foot">{{ $stats['gcp_latest_period'] ?? 'No reports yet' }} &middot; {{ $stats['gcp_reports'] }} {{ \Illuminate\Support\Str::plural('report', $stats['gcp_reports']) }}</div>
                <i class="bi bi-cloud kpi-icon"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card kpi-card kpi-amber h-100">
            <div class="card-body">
                <div class="kpi-label">Expiring Soon (30d)</div>
                <div class="kpi-value">{{ number_format($stats['expiring_total']) }}</div>
                <div class="kpi-foot">{{ $stats['expiring_subs'] }} subs &middot; {{ $stats['expiring_licenses'] }} licenses</div>
                <i class="bi bi-exclamation-triangle kpi-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-bar-chart-fill text-primary"></i> Inventory Health</h6>
                    <span class="text-muted small">PC Master &amp; Device Master</span>
                </div>
                <div style="position: relative; height: 240px;">
                    <canvas id="inventoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body p-3 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Activity</h6>
                    @if($user->isAdmin())
                        <a href="{{ route('activity-logs.index') }}" class="small text-decoration-none">View all</a>
                    @endif
                </div>
                <div class="flex-grow-1" style="max-height: 240px; overflow-y: auto;">
                    @forelse($recentActivity as $log)
                        @php
                            $iconMap = [
                                'created'      => ['bi-plus-circle',      'success'],
                                'updated'      => ['bi-pencil-square',    'info'],
                                'deleted'      => ['bi-trash',            'danger'],
                                'imported'     => ['bi-upload',           'warning'],
                                'renewed'      => ['bi-arrow-repeat',     'primary'],
                                'login'        => ['bi-box-arrow-in-right', 'secondary'],
                                'logout'       => ['bi-box-arrow-right',  'secondary'],
                                'login_failed' => ['bi-shield-exclamation', 'danger'],
                            ];
                            [$icon, $tone] = $iconMap[$log->action] ?? ['bi-circle-fill', 'secondary'];
                        @endphp
                        <div class="activity-item">
                            <span class="activity-icon bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis"><i class="bi {{ $icon }}"></i></span>
                            <div class="activity-body">
                                <div class="text-truncate" title="{{ $log->description }}">{{ $log->description }}</div>
                                <div class="activity-meta">{{ $log->user_name ?: '—' }} &middot; {{ $log->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small text-center py-4 mb-0">No activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-event text-primary"></i> Subscriptions Expiring (30d)</h6>
                <a href="{{ route('subscriptions.index') }}" class="small text-decoration-none">View all</a>
            </div>
            <div class="card-body p-0">
                @if($expiringSoon->isEmpty())
                    <div class="text-center text-muted py-4 small">
                        <i class="bi bi-check-circle text-success"></i> Nothing expiring in the next 30 days.
                    </div>
                @else
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Expires</th>
                                <th class="text-end">Days</th>
                                @if($user->isAdmin())<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringSoon as $sub)
                                @php
                                    $days = (int) \Carbon\Carbon::today()->diffInDays($sub->expire_date, false);
                                    $badge = $days <= 7 ? 'danger' : ($days <= 14 ? 'warning' : 'info');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 220px;" title="{{ $sub->subscription_name }}">{{ $sub->subscription_name }}</div>
                                        <div class="text-muted small">{{ $sub->service_type }} &middot; {{ $sub->project_name }}</div>
                                    </td>
                                    <td class="small">{{ $sub->expire_date->format('Y-m-d') }}</td>
                                    <td class="text-end"><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}-emphasis">{{ $days }}d</span></td>
                                    @if($user->isAdmin())
                                    <td class="text-end pe-3">
                                        <a href="{{ route('subscriptions.edit', $sub) }}" class="btn btn-sm btn-outline-primary py-0">Open</a>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-file-earmark-text text-primary"></i> Licenses Expiring (30d)</h6>
                <a href="{{ route('licenses-contracts.index') }}" class="small text-decoration-none">View all</a>
            </div>
            <div class="card-body p-0">
                @if($expiringLicenses->isEmpty())
                    <div class="text-center text-muted py-4 small">
                        <i class="bi bi-check-circle text-success"></i> Nothing expiring in the next 30 days.
                    </div>
                @else
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Software / Contract</th>
                                <th>Expires</th>
                                <th class="text-end">Days</th>
                                @if($user->isAdmin())<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringLicenses as $lic)
                                @php
                                    $days = (int) \Carbon\Carbon::today()->diffInDays($lic->expire_date, false);
                                    $badge = $days <= 7 ? 'danger' : ($days <= 14 ? 'warning' : 'info');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 220px;" title="{{ $lic->software_name }}">{{ $lic->software_name }}</div>
                                        <div class="text-muted small">{{ $lic->vendor_name ?? '—' }}</div>
                                    </td>
                                    <td class="small">{{ $lic->expire_date->format('Y-m-d') }}</td>
                                    <td class="text-end"><span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}-emphasis">{{ $days }}d</span></td>
                                    @if($user->isAdmin())
                                    <td class="text-end pe-3">
                                        <a href="{{ route('licenses-contracts.edit', $lic) }}" class="btn btn-sm btn-outline-primary py-0">Open</a>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $pcStatuses     = ['Active', 'Free', 'Damage', 'Retirement', 'Low Performance'];
    $deviceStatuses = ['Active', 'Free', 'Damage', 'Retirement', 'Lost'];
    $labels = array_values(array_unique(array_merge($pcStatuses, $deviceStatuses)));
    $pcData     = array_map(fn ($s) => (int) ($assetStatusCounts[$s] ?? 0), $labels);
    $deviceData = array_map(fn ($s) => (int) ($deviceStatusCounts[$s] ?? 0), $labels);
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const ctx = document.getElementById('inventoryChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($labels),
                datasets: [
                    {
                        label: 'PC Master',
                        data: @json($pcData),
                        backgroundColor: 'rgba(13, 110, 253, 0.75)',
                        borderRadius: 6,
                        maxBarThickness: 36,
                    },
                    {
                        label: 'Device Master',
                        data: @json($deviceData),
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        borderRadius: 6,
                        maxBarThickness: 36,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } },
                },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            label: (c) => `${c.dataset.label}: ${c.parsed.y}`,
                        },
                    },
                },
            },
        });
    })();
</script>
@endpush
