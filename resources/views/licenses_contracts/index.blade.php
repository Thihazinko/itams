@extends('layouts.app')

@section('title', 'License & Contract')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $kpiTotal    = (int) ($kpis['total']    ?? 0);
    $kpiActive   = (int) ($kpis['active']   ?? 0);
    $kpiExpiring = (int) ($kpis['expiring'] ?? 0);
    $kpiPending  = (int) ($kpis['pending']  ?? 0);
    $kpiExpired  = (int) ($kpis['expired']  ?? 0);
    $base = route('licenses-contracts.index');
    $activeKpi    = request('status') === 'Active' && !request('expiring_soon') && !request('overdue');
    $expiringKpi  = (bool) request('expiring_soon') && !request('status') && !request('overdue');
    $pendingKpi   = request('status') === 'Pending' && !request('expiring_soon') && !request('overdue');
    $overdueKpi   = (bool) request('overdue');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">License &amp; Contract</h1>
        <div class="page-subtitle">Software licenses, support agreements, and vendor contracts.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('licenses-contracts.export', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ route('licenses-contracts.export', ['format' => 'csv']) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
            </ul>
        </div>
        @if($isAdmin)
        <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#importLcModal">
            <i class="bi bi-upload"></i> Import
        </button>
        <a href="{{ route('licenses-contracts.create') }}" class="quick-action quick-action-primary">
            <i class="bi bi-plus-circle"></i> Add License/Contract
        </a>
        @endif
    </div>
</div>

@if($isAdmin)
<div class="modal fade" id="importLcModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('licenses-contracts.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import License &amp; Contract</h5>
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
                        <a href="{{ route('licenses-contracts.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-file-earmark-excel"></i> Excel template
                        </a>
                        <a href="{{ route('licenses-contracts.template', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-file-earmark-text"></i> CSV template
                        </a>
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

<div id="lcContent" class="position-relative">
    <div id="lcLoadingOverlay" class="lc-loading-overlay d-none">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
    </div>

    <div class="stat-row mb-3" style="--stat-cols: 4;">
        <a class="stat-cell stat-link {{ $activeKpi ? 'is-active' : '' }}"
           href="{{ $base . '?status=Active' }}"
           style="--stat-color: #10b981;"
           title="Show only Active licenses & contracts">
            <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ number_format($kpiActive) }}</div>
                <div class="stat-foot">of {{ number_format($kpiTotal) }} total records</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $expiringKpi ? 'is-active' : '' }}"
           href="{{ $base . '?expiring_soon=1' }}"
           style="--stat-color: #f59e0b;"
           title="Show licenses & contracts expiring within 30 days">
            <span class="stat-icon"><i class="bi bi-clock-history"></i></span>
            <div class="stat-body">
                <div class="stat-label">Expiring ≤30d</div>
                <div class="stat-value">{{ number_format($kpiExpiring) }}</div>
                <div class="stat-foot">Renewal window open</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $pendingKpi ? 'is-active' : '' }}"
           href="{{ $base . '?status=Pending' }}"
           style="--stat-color: #6366f1;"
           title="Show Pending licenses & contracts">
            <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="stat-body">
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ number_format($kpiPending) }}</div>
                <div class="stat-foot">Awaiting approval</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $overdueKpi ? 'is-active' : '' }}"
           href="{{ $base . '?overdue=1' }}"
           style="--stat-color: #ef4444;"
           title="Show expired and overdue records">
            <span class="stat-icon"><i class="bi bi-exclamation-octagon"></i></span>
            <div class="stat-body">
                <div class="stat-label">Expired / Overdue</div>
                <div class="stat-value">{{ number_format($kpiExpired) }}</div>
                <div class="stat-foot">Past expiry, action needed</div>
            </div>
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-body p-3 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Expiring within 30 Days</h6>
                        <span class="badge bg-warning-subtle text-warning-emphasis">{{ $expiringSoon->count() }}</span>
                    </div>
                    <div class="flex-grow-1" style="max-height: 220px; overflow-y: auto;">
                        @forelse($expiringSoon as $item)
                            @php
                                $daysLeft = (int) \Carbon\Carbon::today()->diffInDays($item->expire_date, false);
                                $tone = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 14 ? 'warning' : 'info');
                            @endphp
                            <div class="d-flex gap-2 py-2 border-bottom border-light-subtle align-items-center">
                                <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis align-self-start" style="min-width: 64px;">{{ $daysLeft }}d left</span>
                                <div class="flex-grow-1 small" style="line-height: 1.3; min-width: 0;">
                                    <div class="text-truncate" title="{{ $item->software_name }}{{ $item->vendor_name ? ' · ' . $item->vendor_name : '' }}">
                                        <strong>{{ $item->software_name }}</strong>@if($item->vendor_name) &middot; {{ $item->vendor_name }} @endif
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        {{ $item->renewal_type }} &middot; expires {{ $item->expire_date->format('Y-m-d') }}
                                    </div>
                                </div>
                                <a href="{{ route('licenses-contracts.edit', $item) }}" class="btn-icon-soft" title="Edit"><i class="bi bi-pencil"></i></a>
                            </div>
                        @empty
                            <p class="text-muted small text-center py-4 mb-0"><i class="bi bi-check-circle text-success"></i> Nothing expiring in the next 30 days.</p>
                        @endforelse
                    </div>
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
                    <div class="flex-grow-1" style="max-height: 220px; overflow-y: auto;">
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
                            <p class="text-muted small text-center py-4 mb-0">No license/contract changes recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" id="lcFilterForm" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Search software, vendor, license info…">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['Active', 'Pending', 'Expired', 'Terminated'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    @if(request()->hasAny(['search','status','expiring_soon','overdue']))
                        <a href="{{ route('licenses-contracts.index') }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <form id="lcBulkForm" action="{{ route('licenses-contracts.bulk-destroy') }}" method="POST">
        @csrf @method('DELETE')

        @if($isAdmin)
        <div id="lcBulkToolbar" class="card mb-2 d-none">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="small">
                    <i class="bi bi-check2-square text-primary"></i>
                    <strong id="lcBulkCount">0</strong> selected
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="lcBulkClear">Clear</button>
                    <button type="submit" class="btn btn-sm btn-danger">
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
                                    <input type="checkbox" id="lcSelectAll" class="form-check-input" title="Select all on page">
                                </th>
                            @endif
                            <th style="width: 60px;">No</th>
                            <th>Software / Contract</th>
                            <th>Status</th>
                            <th>Vendor</th>
                            <th>License / Serial / Invoice</th>
                            <th>Last Renewal</th>
                            <th>Expires</th>
                            <th>Renewal</th>
                            <th class="text-end">Cost</th>
                            <th>Price Change</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i => $item)
                            @php
                                $today = \Carbon\Carbon::today();
                                $days = (int) $today->diffInDays($item->expire_date, false);
                                $isOverdue = $days < 0 && !in_array($item->status, ['Terminated']);
                                $statusTone = match($item->status) {
                                    'Active'     => 'success',
                                    'Pending'    => 'warning',
                                    'Expired'    => 'danger',
                                    'Terminated' => 'secondary',
                                    default      => 'secondary',
                                };

                                $prev = $item->previous_cost !== null ? (float) $item->previous_cost : null;
                                $curr = $item->renewal_cost  !== null ? (float) $item->renewal_cost  : null;
                                $priceChange = null;
                                if ($prev !== null && $curr !== null) {
                                    $diff = $curr - $prev;
                                    $pct = $prev > 0 ? ($diff / $prev) * 100 : null;
                                    if (abs($diff) < 0.005) {
                                        $priceChange = ['label' => 'No change', 'tone' => 'secondary', 'icon' => 'bi-dash', 'diff' => 0, 'pct' => $pct];
                                    } elseif ($diff > 0) {
                                        $priceChange = ['label' => 'Up',   'tone' => 'danger',  'icon' => 'bi-arrow-up',   'diff' => $diff, 'pct' => $pct];
                                    } else {
                                        $priceChange = ['label' => 'Down', 'tone' => 'success', 'icon' => 'bi-arrow-down', 'diff' => $diff, 'pct' => $pct];
                                    }
                                }
                            @endphp
                            <tr>
                                @if($isAdmin)
                                    <td>
                                        <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="form-check-input lc-row-check">
                                    </td>
                                @endif
                                <td class="text-muted small">{{ ($items->firstItem() ?? 1) + $i }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $item->software_name }}</div>
                                    @if($item->remarks)
                                        <div class="text-muted small text-truncate" style="max-width: 280px;" title="{{ $item->remarks }}">{{ $item->remarks }}</div>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis">{{ $item->status }}</span></td>
                                <td>{{ $item->vendor_name ?: '—' }}</td>
                                <td>
                                    @if($item->license_info)
                                        <span class="d-inline-block text-truncate" style="max-width: 220px;" title="{{ $item->license_info }}">
                                            <code class="small">{{ $item->license_info }}</code>
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted small text-nowrap">{{ $item->last_renewal_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-nowrap">
                                    <div class="small">{{ $item->expire_date->format('Y-m-d') }}</div>
                                    @if($isOverdue)
                                        <span class="badge bg-danger-subtle text-danger-emphasis" style="font-size:.65rem;">{{ abs($days) }}d overdue</span>
                                    @elseif($item->status === 'Active' && $days <= 30)
                                        <span class="badge bg-warning-subtle text-warning-emphasis" style="font-size:.65rem;">{{ $days }}d left</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $item->renewal_type }}</td>
                                <td class="text-end">
                                    @if($item->renewal_cost !== null)
                                        <span class="fw-semibold">{{ number_format((float) $item->renewal_cost, 2) }}</span>
                                        <span class="text-muted small ms-1">{{ $item->currency ?? 'MMK' }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($priceChange)
                                        <span class="badge bg-{{ $priceChange['tone'] }}-subtle text-{{ $priceChange['tone'] }}-emphasis d-inline-flex align-items-center gap-1"
                                              title="Previous: {{ number_format($prev, 2) }} → Renewal: {{ number_format($curr, 2) }}">
                                            <i class="bi {{ $priceChange['icon'] }}"></i>
                                            {{ $priceChange['label'] }}
                                            @if($priceChange['pct'] !== null && $priceChange['diff'] != 0)
                                                {{ $priceChange['diff'] > 0 ? '+' : '' }}{{ number_format($priceChange['pct'], 1) }}%
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap pe-3">
                                    <a href="{{ route('licenses-contracts.edit', $item) }}" class="btn-icon-soft" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    @if($isAdmin)
                                    @php
                                        $lcParts = [];
                                        if ($item->vendor_name) $lcParts[] = $item->vendor_name;
                                        if ($item->expire_date) $lcParts[] = 'expires ' . $item->expire_date->format('Y-m-d');
                                        $lcDetail = implode(' · ', $lcParts);
                                    @endphp
                                    <button type="button" class="btn-icon-soft text-danger lc-delete-single"
                                            title="Delete" aria-label="Delete"
                                            data-id="{{ $item->id }}"
                                            data-label="{{ $item->software_name }}"
                                            data-detail="{{ $lcDetail }}"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 11 : 10 }}" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        <div class="fw-semibold">No licenses or contracts found</div>
                                        <div class="small">
                                            @if(request()->hasAny(['search','status','expiring_soon','overdue']))
                                                Try clearing the filters or <a href="{{ route('licenses-contracts.index') }}">view all</a>.
                                            @elseif($isAdmin)
                                                <a href="{{ route('licenses-contracts.create') }}">Add the first license/contract</a> to get started.
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
    <form id="lcSingleDeleteForm" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>
    @endif

    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function refreshBulkToolbar() {
        const checks  = document.querySelectorAll('.lc-row-check');
        const toolbar = document.getElementById('lcBulkToolbar');
        const countEl = document.getElementById('lcBulkCount');
        const selAll  = document.getElementById('lcSelectAll');
        const selected = Array.from(checks).filter(c => c.checked).length;
        if (countEl) countEl.textContent = selected;
        if (toolbar) toolbar.classList.toggle('d-none', selected === 0);
        if (selAll) {
            selAll.checked = selected > 0 && selected === checks.length;
            selAll.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    async function swap(url, { push = true } = {}) {
        const container = document.getElementById('lcContent');
        if (!container) { window.location.href = url; return; }
        const overlay = document.getElementById('lcLoadingOverlay');
        if (overlay) overlay.classList.remove('d-none');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('lcContent');
            if (!fresh) throw new Error('Missing #lcContent in response');
            container.replaceWith(fresh);
            if (push) history.pushState({ lcContent: true }, '', url);
            refreshBulkToolbar();
        } catch (err) {
            if (overlay) overlay.classList.add('d-none');
            window.location.href = url;
        }
    }

    // Filter form
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#lcFilterForm');
        if (!form) return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form)).toString();
        const url = form.action ? form.action.split('?')[0] : window.location.pathname;
        swap(params ? `${url}?${params}` : url);
    });

    // Pagination
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#lcContent .pagination a.page-link');
        if (!link || link.getAttribute('href') === '#' || !link.href) return;
        e.preventDefault();
        swap(link.href);
    });

    // Clear-filters X
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#lcContent a[href$="/licenses-contracts"]');
        if (!link || !link.querySelector('.bi-x-lg')) return;
        e.preventDefault();
        swap(link.href);
    });

    // KPI tile click → filter via AJAX swap
    document.addEventListener('click', (e) => {
        const tile = e.target.closest('#lcContent a.stat-link');
        if (!tile || !tile.href) return;
        e.preventDefault();
        swap(tile.href);
    });

    // Bulk select
    document.addEventListener('change', (e) => {
        if (e.target.id === 'lcSelectAll') {
            document.querySelectorAll('.lc-row-check').forEach(c => { c.checked = e.target.checked; });
            refreshBulkToolbar();
        } else if (e.target.classList.contains('lc-row-check')) {
            refreshBulkToolbar();
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('#lcBulkClear')) {
            document.querySelectorAll('.lc-row-check').forEach(c => { c.checked = false; });
            refreshBulkToolbar();
        }
    });

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#lcBulkForm');
        if (!form) return;
        if (form.dataset.bulkConfirmed === '1') return;
        const selected = document.querySelectorAll('.lc-row-check:checked').length;
        if (selected === 0) {
            e.preventDefault();
            alert('Select at least one license/contract to delete.');
            return;
        }
        e.preventDefault();
        appConfirm({
            title: `Delete ${selected} record(s)?`,
            message: `You are about to permanently delete <strong>${selected}</strong> selected license/contract record(s).`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete all',
        }).then((ok) => {
            if (!ok) return;
            form.dataset.bulkConfirmed = '1';
            form.submit();
        });
    });

    // Single delete
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.lc-delete-single');
        if (!btn) return;
        const detail = btn.dataset.detail
            ? `<br><small class="text-muted">${appHtmlEscape(btn.dataset.detail)}</small>`
            : '';
        appConfirm({
            title: 'Delete this license/contract?',
            message: `You are about to permanently delete <strong>${appHtmlEscape(btn.dataset.label)}</strong>.${detail}`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('lcSingleDeleteForm');
            if (!form) return;
            form.action = `{{ url('licenses-contracts') }}/${btn.dataset.id}`;
            form.submit();
        });
    });

    window.addEventListener('popstate', () => {
        swap(window.location.href, { push: false });
    });

    refreshBulkToolbar();
})();
</script>

<style>
    .lc-loading-overlay {
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
    [data-bs-theme="dark"] .lc-loading-overlay {
        background: rgba(15, 20, 27, 0.6);
    }
</style>
@endpush
