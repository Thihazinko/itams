@extends('layouts.app')

@section('title', 'Device Master')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('devices');
    $statusOrder = ['Active', 'Free', 'Damage', 'Retirement', 'Lost'];
    $chartData = array_map(fn ($s) => (int) ($statusCounts[$s] ?? 0), $statusOrder);
    $chartTotal = array_sum($chartData);
    $kpiTotal     = $chartTotal;
    $kpiUnits     = $devices->total() === 0 ? 0 : (int) \App\Models\Device::sum('qty');
    $kpiActive    = (int) ($statusCounts['Active'] ?? 0);
    $kpiFree      = (int) ($statusCounts['Free'] ?? 0);
    $kpiDamage    = (int) ($statusCounts['Damage'] ?? 0);
    $kpiRetire    = (int) ($statusCounts['Retirement'] ?? 0);
    $kpiLost      = (int) ($statusCounts['Lost'] ?? 0);
    $kpiAttention = $kpiDamage + $kpiRetire + $kpiLost;
    $pct = fn ($n) => $kpiTotal > 0 ? round($n / $kpiTotal * 100) : 0;

    $base = route('devices.index');
    $totalKpi     = !request('status') && !request('attention') && !request('search');
    $activeKpi    = request('status') === 'Active' && !request('attention');
    $freeKpi      = request('status') === 'Free'   && !request('attention');
    $attentionKpi = (bool) request('attention');

    // Toggleable table columns (key => label) and which are shown by default.
    $deviceColumns = [
        'category'          => 'Category',
        'item_name'         => 'Item Name',
        'serial'            => 'Serial Number',
        'location'          => 'Location',
        'qty'               => 'Qty',
        'status'            => 'Status',
        'vendor'            => 'Vendor',
        'purchased'         => 'Purchased',
        'warranty'          => 'Warranty',
        'delivered'         => 'Delivered',
        'delivery_location' => 'Delivery Location',
        'remark'            => 'Remark',
    ];
    $deviceDefaultCols = ['category', 'item_name', 'serial', 'location', 'qty', 'status', 'vendor', 'purchased', 'warranty', 'delivered'];
    // Helper: classes for a column cell — adds d-none when hidden by default.
    $colClass = fn ($key) => 'device-col device-col-' . $key . (in_array($key, $deviceDefaultCols) ? '' : ' d-none');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Device Master</h1>
        <div class="page-subtitle">Peripherals, network gear, and standalone devices in your inventory.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('devices.export', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ route('devices.export', ['format' => 'csv']) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
            </ul>
        </div>
        @if($canEdit)
        <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#importDeviceModal">
            <i class="bi bi-upload"></i> Import
        </button>
        @endif
        @if($isAdmin)
        <a href="{{ route('devices.create') }}" class="quick-action quick-action-primary">
            <i class="bi bi-plus-circle"></i> Add Device
        </a>
        @endif
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="importDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('devices.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import Devices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Spreadsheet file <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">Accepted: .xlsx, .xls, .csv (max 10 MB). First row must contain the column headers.</small>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <strong>Need a template?</strong> Download a sample file with the required headers:<br>
                        <a href="{{ route('devices.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-file-earmark-excel"></i> Excel template
                        </a>
                        <a href="{{ route('devices.template', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-file-earmark-text"></i> CSV template
                        </a>
                        <hr class="my-2">
                        <div class="mb-0">
                            Rows are matched by <code>serial_number</code> &mdash; existing serials are skipped, blank serials are always inserted as new rows.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div id="deviceContent" class="position-relative">
    <div id="deviceLoadingOverlay" class="device-loading-overlay d-none">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
    </div>

    <div class="stat-row mb-3">
        <a class="stat-cell stat-link {{ $totalKpi ? 'is-active' : '' }}"
           href="{{ $base }}"
           style="--stat-color: #0d6efd;"
           title="Show all devices (clear filters)">
            <span class="stat-icon"><i class="bi bi-hdd-network"></i></span>
            <div class="stat-body">
                <div class="stat-label">Total Items</div>
                <div class="stat-value">{{ number_format($kpiTotal) }}</div>
                <div class="stat-foot">{{ number_format($kpiUnits) }} unit{{ $kpiUnits === 1 ? '' : 's' }} total</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $activeKpi ? 'is-active' : '' }}"
           href="{{ $base . '?status=Active' }}"
           style="--stat-color: #10b981;"
           title="Show only Active devices">
            <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ number_format($kpiActive) }}</div>
                <div class="stat-foot">{{ $pct($kpiActive) }}% of inventory</div>
                <div class="stat-bar"><span style="width: {{ $pct($kpiActive) }}%"></span></div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $freeKpi ? 'is-active' : '' }}"
           href="{{ $base . '?status=Free' }}"
           style="--stat-color: #8b5cf6;"
           title="Show only Free / Spare devices">
            <span class="stat-icon"><i class="bi bi-archive"></i></span>
            <div class="stat-body">
                <div class="stat-label">Free / Spare</div>
                <div class="stat-value">{{ number_format($kpiFree) }}</div>
                <div class="stat-foot">{{ $pct($kpiFree) }}% available</div>
                <div class="stat-bar"><span style="width: {{ $pct($kpiFree) }}%"></span></div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $attentionKpi ? 'is-active' : '' }}"
           href="{{ $base . '?attention=1' }}"
           style="--stat-color: #f59e0b;"
           title="Show devices needing attention (Damage, Retirement, Lost)">
            <span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Needs Attention</div>
                <div class="stat-value">{{ number_format($kpiAttention) }}</div>
                <div class="stat-foot">
                    <span title="Damage">{{ $kpiDamage }}<span class="opacity-50">D</span></span>
                    &middot;
                    <span title="Retirement">{{ $kpiRetire }}<span class="opacity-50">R</span></span>
                    &middot;
                    <span title="Lost">{{ $kpiLost }}<span class="opacity-50">L</span></span>
                </div>
                <div class="stat-bar"><span style="width: {{ $pct($kpiAttention) }}%"></span></div>
            </div>
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-bar-chart-fill text-primary"></i> Status Breakdown</h6>
                        <span class="text-muted small">{{ $chartTotal }} item{{ $chartTotal === 1 ? '' : 's' }}</span>
                    </div>
                    @if($chartTotal > 0)
                        <div style="position: relative; height: 200px;">
                            <canvas id="deviceStatusChart"
                                    data-chart-labels='@json($statusOrder)'
                                    data-chart-data='@json($chartData)'></canvas>
                        </div>
                    @else
                        <p class="text-muted small mb-0 text-center py-4">No data to display for the current filters.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-body p-3 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Changes</h6>
                        @if($isAdmin)
                            <a href="{{ route('activity-logs.index') }}" class="small text-decoration-none">View all</a>
                        @endif
                    </div>
                    <div class="flex-grow-1" style="max-height: 200px; overflow-y: auto;">
                        @forelse($recentLogs as $log)
                            @php
                                $iconMap = [
                                    'created'  => ['bi-plus-circle',   'success'],
                                    'updated'  => ['bi-pencil-square', 'info'],
                                    'deleted'  => ['bi-trash',         'danger'],
                                    'imported' => ['bi-upload',        'warning'],
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
                            <p class="text-muted small text-center py-4 mb-0">No device changes recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" id="deviceFilterForm" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Search item, serial, vendor, location...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($statusOrder as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    @if(request()->hasAny(['search','status']))
                        <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-2">
        <div class="dropdown">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">
                <i class="bi bi-layout-three-columns"></i> Columns
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end p-2" style="max-height: 340px; overflow-y: auto; min-width: 220px;">
                <li class="d-flex justify-content-between align-items-center px-2 pb-1 mb-1 border-bottom">
                    <span class="small text-muted fw-semibold">Columns</span>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="deviceColReset" data-defaults='@json($deviceDefaultCols)'>Reset</button>
                </li>
                @foreach($deviceColumns as $key => $label)
                    <li>
                        <label class="dropdown-item d-flex align-items-center gap-2 px-2 rounded">
                            <input type="checkbox" class="form-check-input mt-0 device-col-toggle" data-col="{{ $key }}" @checked(in_array($key, $deviceDefaultCols))>
                            <span>{{ $label }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <form id="deviceBulkForm" action="{{ route('devices.bulk-destroy') }}" method="POST">
        @csrf @method('DELETE')

        @if($isAdmin)
        <div id="deviceBulkToolbar" class="card mb-2 d-none">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="small">
                    <i class="bi bi-check2-square text-primary"></i>
                    <strong id="deviceBulkCount">0</strong> selected
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deviceBulkClear">Clear</button>
                    <button type="submit" class="btn btn-sm btn-danger" id="deviceBulkDelete">
                        <i class="bi bi-trash"></i> Delete selected
                    </button>
                </div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @if($isAdmin)
                                <th style="width: 38px;">
                                    <input type="checkbox" id="deviceSelectAll" class="form-check-input" title="Select all on page">
                                </th>
                            @endif
                            <th style="width: 60px;">No</th>
                            <th class="{{ $colClass('category') }}">Category</th>
                            <th class="{{ $colClass('item_name') }}">Item Name</th>
                            <th class="{{ $colClass('serial') }}">Serial Number</th>
                            <th class="{{ $colClass('location') }}">Location</th>
                            <th class="{{ $colClass('qty') }} text-end">Qty</th>
                            <th class="{{ $colClass('status') }}">Status</th>
                            <th class="{{ $colClass('vendor') }}">Vendor</th>
                            <th class="{{ $colClass('purchased') }}">Purchased</th>
                            <th class="{{ $colClass('warranty') }}">Warranty</th>
                            <th class="{{ $colClass('delivered') }}">Delivered</th>
                            <th class="{{ $colClass('delivery_location') }}">Delivery Location</th>
                            <th class="{{ $colClass('remark') }}">Remark</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $i => $device)
                            <tr>
                                @if($isAdmin)
                                    <td>
                                        <input type="checkbox" name="ids[]" value="{{ $device->id }}" class="form-check-input device-row-check">
                                    </td>
                                @endif
                                <td>{{ ($devices->firstItem() ?? 1) + $i }}</td>
                                <td class="{{ $colClass('category') }}">
                                    @if($device->category)
                                        <span class="badge bg-light text-dark border">{{ $device->category }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="{{ $colClass('item_name') }}">
                                    <a href="{{ route('devices.show', $device) }}" class="fw-semibold text-decoration-none">{{ $device->item_name }}</a>
                                    @if($device->description)
                                        <div class="text-muted small text-truncate" style="max-width: 280px;" title="{{ $device->description }}">{{ $device->description }}</div>
                                    @endif
                                </td>
                                <td class="{{ $colClass('serial') }}">
                                    @if($device->serial_number)
                                        <code class="small">{{ $device->serial_number }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="{{ $colClass('location') }}">{{ $device->location ?: '—' }}</td>
                                <td class="{{ $colClass('qty') }} text-end fw-semibold">{{ $device->qty }}</td>
                                <td class="{{ $colClass('status') }}">
                                    @php $tone = match($device->status) {
                                        'Active'     => 'success',
                                        'Free'       => 'info',
                                        'Damage'     => 'danger',
                                        'Retirement' => 'secondary',
                                        'Lost'       => 'warning',
                                        default      => 'secondary',
                                    }; @endphp
                                    <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis">{{ $device->status }}</span>
                                </td>
                                <td class="{{ $colClass('vendor') }}">{{ $device->vendor ?: '—' }}</td>
                                <td class="{{ $colClass('purchased') }} text-muted small">{{ $device->purchased_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="{{ $colClass('warranty') }} text-muted small">{{ $device->warranty ?: '—' }}</td>
                                <td class="{{ $colClass('delivered') }} text-muted small">{{ $device->delivery_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="{{ $colClass('delivery_location') }}">{{ $device->delivery_location ?: '—' }}</td>
                                <td class="{{ $colClass('remark') }} text-truncate" style="max-width: 220px;" title="{{ $device->remark }}">{{ $device->remark ?: '—' }}</td>
                                <td class="text-end text-nowrap pe-3">
                                    <a href="{{ route('devices.show', $device) }}" class="btn-icon-soft" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('devices.edit', $device) }}" class="btn-icon-soft" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    @if($isAdmin)
                                    @php
                                        $dvParts = [];
                                        if ($device->vendor)         $dvParts[] = $device->vendor;
                                        if ($device->qty)            $dvParts[] = '×' . $device->qty;
                                        if ($device->serial_number)  $dvParts[] = 'S/N ' . $device->serial_number;
                                        $dvDetail = implode(' · ', $dvParts);
                                    @endphp
                                    <button type="button" class="btn-icon-soft text-danger device-delete-single"
                                            title="Delete" aria-label="Delete"
                                            data-id="{{ $device->id }}"
                                            data-label="{{ $device->item_name }}"
                                            data-detail="{{ $dvDetail }}"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 15 : 14 }}" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        <div class="fw-semibold">No devices found</div>
                                        <div class="small">
                                            @if(request()->hasAny(['search','status']))
                                                Try clearing the filters or <a href="{{ route('devices.index') }}">view all</a>.
                                            @elseif($isAdmin)
                                                <a href="{{ route('devices.create') }}">Add the first device</a> to get started.
                                            @else
                                                No records have been added yet.
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    @if($isAdmin)
    <form id="deviceSingleDeleteForm" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>
    @endif

    <div class="mt-3">{{ $devices->links() }}</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const STATUS_COLORS = [
        'rgba(25, 135, 84, 0.75)',   // Active
        'rgba(13, 202, 240, 0.75)',  // Free
        'rgba(220, 53, 69, 0.75)',   // Damage
        'rgba(108, 117, 125, 0.75)', // Retirement
        'rgba(255, 193, 7, 0.85)',   // Lost
    ];

    let chartInstance = null;
    function initChart() {
        const ctx = document.getElementById('deviceStatusChart');
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
        if (!ctx) return;
        const labels = JSON.parse(ctx.dataset.chartLabels || '[]');
        const data   = JSON.parse(ctx.dataset.chartData   || '[]');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Devices',
                    data: data,
                    backgroundColor: STATUS_COLORS.slice(0, labels.length),
                    borderRadius: 6,
                    maxBarThickness: 48,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total ? ((ctx.parsed.y / total) * 100).toFixed(1) : 0;
                                return `${ctx.parsed.y} item(s) (${pct}%)`;
                            }
                        }
                    },
                },
            },
        });
    }

    function refreshBulkToolbar() {
        const checks  = document.querySelectorAll('.device-row-check');
        const toolbar = document.getElementById('deviceBulkToolbar');
        const countEl = document.getElementById('deviceBulkCount');
        const selAll  = document.getElementById('deviceSelectAll');
        const selected = Array.from(checks).filter(c => c.checked).length;
        if (countEl) countEl.textContent = selected;
        if (toolbar) toolbar.classList.toggle('d-none', selected === 0);
        if (selAll) {
            selAll.checked = selected > 0 && selected === checks.length;
            selAll.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    async function swap(url, { push = true } = {}) {
        const container = document.getElementById('deviceContent');
        if (!container) { window.location.href = url; return; }
        const overlay = document.getElementById('deviceLoadingOverlay');
        if (overlay) overlay.classList.remove('d-none');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('deviceContent');
            if (!fresh) throw new Error('Missing #deviceContent in response');
            container.replaceWith(fresh);
            if (push) history.pushState({ deviceContent: true }, '', url);
            initChart();
            refreshBulkToolbar();
            restoreColumnPrefs();
        } catch (err) {
            if (overlay) overlay.classList.add('d-none');
            window.location.href = url;
        }
    }

    // Filter form submit
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#deviceFilterForm');
        if (!form) return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form)).toString();
        const url = form.action ? form.action.split('?')[0] : window.location.pathname;
        swap(params ? `${url}?${params}` : url);
    });

    // Pagination
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#deviceContent .pagination a.page-link');
        if (!link || link.getAttribute('href') === '#' || !link.href) return;
        e.preventDefault();
        swap(link.href);
    });

    // Clear-filters X button
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#deviceContent a[href$="/devices"]');
        if (!link || !link.querySelector('.bi-x-lg')) return;
        e.preventDefault();
        swap(link.href);
    });

    // KPI tile click → filter via AJAX swap
    document.addEventListener('click', (e) => {
        const tile = e.target.closest('#deviceContent a.stat-link');
        if (!tile || !tile.href) return;
        e.preventDefault();
        swap(tile.href);
    });

    // Bulk select-all + per-row
    document.addEventListener('change', (e) => {
        if (e.target.id === 'deviceSelectAll') {
            document.querySelectorAll('.device-row-check').forEach(c => { c.checked = e.target.checked; });
            refreshBulkToolbar();
        } else if (e.target.classList.contains('device-row-check')) {
            refreshBulkToolbar();
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('#deviceBulkClear')) {
            document.querySelectorAll('.device-row-check').forEach(c => { c.checked = false; });
            refreshBulkToolbar();
        }
    });

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#deviceBulkForm');
        if (!form) return;
        if (form.dataset.bulkConfirmed === '1') return;
        const selected = document.querySelectorAll('.device-row-check:checked').length;
        if (selected === 0) {
            e.preventDefault();
            alert('Select at least one device to delete.');
            return;
        }
        e.preventDefault();
        appConfirm({
            title: `Delete ${selected} device(s)?`,
            message: `You are about to permanently delete <strong>${selected}</strong> selected device record(s).`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete all',
        }).then((ok) => {
            if (!ok) return;
            form.dataset.bulkConfirmed = '1';
            form.submit();
        });
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.device-delete-single');
        if (!btn) return;
        const id = btn.dataset.id;
        const label = btn.dataset.label;
        const detail = btn.dataset.detail
            ? `<br><small class="text-muted">${appHtmlEscape(btn.dataset.detail)}</small>`
            : '';
        appConfirm({
            title: 'Delete this device?',
            message: `You are about to permanently delete <strong>${appHtmlEscape(label)}</strong>.${detail}`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('deviceSingleDeleteForm');
            if (!form) return;
            form.action = `{{ url('devices') }}/${id}`;
            form.submit();
        });
    });

    // ---- Column show/hide chooser ----
    const COL_STORAGE_KEY = 'deviceMasterColumns';

    // Apply each toggle's checked state to the matching table cells. The chooser
    // sits inside #deviceContent, so an AJAX swap re-renders it with the blade
    // defaults — that's why swap() calls restoreColumnPrefs() (which re-reads
    // localStorage and re-checks the boxes) rather than applyColumnPrefs() alone.
    function applyColumnPrefs() {
        document.querySelectorAll('.device-col-toggle').forEach((cb) => {
            document.querySelectorAll('.device-col-' + cb.dataset.col).forEach((cell) => {
                cell.classList.toggle('d-none', !cb.checked);
            });
        });
    }

    function saveColumnPrefs() {
        const state = {};
        document.querySelectorAll('.device-col-toggle').forEach((cb) => { state[cb.dataset.col] = cb.checked; });
        try { localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(state)); } catch (e) { /* ignore */ }
    }

    function restoreColumnPrefs() {
        let state = null;
        try { state = JSON.parse(localStorage.getItem(COL_STORAGE_KEY) || 'null'); } catch (e) { /* ignore */ }
        if (state) {
            document.querySelectorAll('.device-col-toggle').forEach((cb) => {
                if (Object.prototype.hasOwnProperty.call(state, cb.dataset.col)) cb.checked = !!state[cb.dataset.col];
            });
        }
        applyColumnPrefs();
    }

    document.addEventListener('change', (e) => {
        if (!e.target.classList.contains('device-col-toggle')) return;
        applyColumnPrefs();
        saveColumnPrefs();
    });

    document.addEventListener('click', (e) => {
        const reset = e.target.closest('#deviceColReset');
        if (!reset) return;
        let defaults = [];
        try { defaults = JSON.parse(reset.dataset.defaults || '[]'); } catch (err) { /* ignore */ }
        document.querySelectorAll('.device-col-toggle').forEach((cb) => { cb.checked = defaults.includes(cb.dataset.col); });
        applyColumnPrefs();
        saveColumnPrefs();
    });

    window.addEventListener('popstate', () => {
        swap(window.location.href, { push: false });
    });

    initChart();
    refreshBulkToolbar();
    restoreColumnPrefs();
})();
</script>

<style>
    .device-loading-overlay {
        position: absolute;
        inset: 0;
        z-index: 5;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 30vh;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(2px);
        border-radius: 0.85rem;
    }
    [data-bs-theme="dark"] .device-loading-overlay {
        background: rgba(15, 20, 27, 0.6);
    }
</style>
@endpush
