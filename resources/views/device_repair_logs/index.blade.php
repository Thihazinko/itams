@extends('layouts.app')

@section('title', 'Device Master — Repair Logs')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('devices');

    $statusFilter = in_array(request('status'), \App\Models\DeviceRepairLog::STATUSES, true) ? request('status') : null;

    $total      = array_sum($statusCounts);
    $inProgress = (int) ($statusCounts['In Progress'] ?? 0);
    $completed  = (int) ($statusCounts['Completed'] ?? 0);

    $base = route('device-repair-logs.index');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Device Master</h1>
        <div class="page-subtitle">Repair and maintenance history for company devices.</div>
    </div>
    @if($canEdit)
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('device-repair-logs.export', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ route('device-repair-logs.export', ['format' => 'csv']) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
            </ul>
        </div>
        <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#importDeviceRepairModal">
            <i class="bi bi-upload"></i> Import
        </button>
        <a href="{{ route('device-repair-logs.create') }}" class="quick-action quick-action-primary">
            <i class="bi bi-plus-circle"></i> Add Repair Log
        </a>
    </div>
    @endif
</div>

@include('devices.partials.tabs')

{{-- Status KPI cards --}}
<div class="stat-row mb-3" style="--stat-cols: 3;">
    <a class="stat-cell stat-link {{ $statusFilter === null ? 'is-active' : '' }}"
       href="{{ $base }}"
       style="--stat-color: #0d6efd;"
       title="Show all repair logs">
        <span class="stat-icon"><i class="bi bi-tools"></i></span>
        <div class="stat-body">
            <div class="stat-label">Total Logs</div>
            <div class="stat-value">{{ number_format($total) }}</div>
            <div class="stat-foot">All repair records</div>
        </div>
    </a>
    <a class="stat-cell stat-link {{ $statusFilter === 'In Progress' ? 'is-active' : '' }}"
       href="{{ $base . '?status=' . urlencode('In Progress') }}"
       style="--stat-color: #f59e0b;"
       title="Show only In Progress repairs">
        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
        <div class="stat-body">
            <div class="stat-label">In Progress</div>
            <div class="stat-value">{{ number_format($inProgress) }}</div>
            <div class="stat-foot">{{ $total > 0 ? round($inProgress / $total * 100) : 0 }}% of logs</div>
        </div>
    </a>
    <a class="stat-cell stat-link {{ $statusFilter === 'Completed' ? 'is-active' : '' }}"
       href="{{ $base . '?status=Completed' }}"
       style="--stat-color: #10b981;"
       title="Show only Completed repairs">
        <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
        <div class="stat-body">
            <div class="stat-label">Completed</div>
            <div class="stat-value">{{ number_format($completed) }}</div>
            <div class="stat-foot">{{ $total > 0 ? round($completed / $total * 100) : 0 }}% of logs</div>
        </div>
    </a>
</div>

{{-- Recent changes --}}
<div class="card mb-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Changes</h6>
            @if($isAdmin)
                <a href="{{ route('activity-logs.index') }}" class="small text-decoration-none">View all</a>
            @endif
        </div>
        <div style="max-height: 240px; overflow-y: auto;">
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
                <p class="text-muted small text-center py-4 mb-0">No repair-log changes recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Search --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ $base }}" class="row g-2 align-items-center">
            @if($statusFilter !== null)
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0"
                           placeholder="Search device, employee, department, repair process, remark…">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                @if(request('search') !== null && request('search') !== '')
                    <a href="{{ $base . ($statusFilter !== null ? '?status=' . urlencode($statusFilter) : '') }}" class="btn btn-outline-secondary" title="Clear search"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
@include('device_repair_logs.partials.table')

{{-- Import modal --}}
@if($canEdit)
<div class="modal fade" id="importDeviceRepairModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('device-repair-logs.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import Repair Logs</h5>
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
                        <a href="{{ route('device-repair-logs.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-file-earmark-excel"></i> Excel template</a>
                        <a href="{{ route('device-repair-logs.template', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-file-earmark-text"></i> CSV template</a>
                        <hr class="my-2">
                        <div class="mb-0">Match each row to a device via the <code>device</code> column (its serial number or item name in Device Master).</div>
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

{{-- Shared single-delete form --}}
<form id="drlSingleDeleteForm" method="POST" class="d-none">
    @csrf @method('DELETE')
</form>
@endif
@endsection

@push('scripts')
<script>
(function () {
    // ---- Bulk select ----
    function refreshBulkToolbar() {
        const checks  = document.querySelectorAll('.drl-row-check');
        const toolbar = document.getElementById('drlBulkToolbar');
        const countEl = document.getElementById('drlBulkCount');
        const selAll  = document.getElementById('drlSelectAll');
        const selected = Array.from(checks).filter(c => c.checked).length;
        if (countEl) countEl.textContent = selected;
        if (toolbar) toolbar.classList.toggle('d-none', selected === 0);
        if (selAll) {
            selAll.checked = selected > 0 && selected === checks.length;
            selAll.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    document.addEventListener('change', (e) => {
        if (e.target.id === 'drlSelectAll') {
            document.querySelectorAll('.drl-row-check').forEach(c => { c.checked = e.target.checked; });
            refreshBulkToolbar();
        } else if (e.target.classList.contains('drl-row-check')) {
            refreshBulkToolbar();
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('#drlBulkClear')) {
            document.querySelectorAll('.drl-row-check').forEach(c => { c.checked = false; });
            refreshBulkToolbar();
        }
    });

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#drlBulkForm');
        if (!form) return;
        if (form.dataset.bulkConfirmed === '1') return;
        const selected = document.querySelectorAll('.drl-row-check:checked').length;
        if (selected === 0) {
            e.preventDefault();
            alert('Select at least one row to delete.');
            return;
        }
        e.preventDefault();
        appConfirm({
            title: `Delete ${selected} repair log(s)?`,
            message: `You are about to permanently delete <strong>${selected}</strong> selected repair log(s).`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete all',
        }).then((ok) => {
            if (!ok) return;
            form.dataset.bulkConfirmed = '1';
            form.submit();
        });
    });

    // ---- Single delete ----
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.drl-delete-single');
        if (!btn) return;
        appConfirm({
            title: 'Delete this repair log?',
            message: `You are about to permanently delete the repair log for <strong>${appHtmlEscape(btn.dataset.label)}</strong>.`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('drlSingleDeleteForm');
            if (!form) return;
            form.action = btn.dataset.action;
            form.submit();
        });
    });

    refreshBulkToolbar();
})();
</script>
@endpush
