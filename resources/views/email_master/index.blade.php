@extends('layouts.app')

@section('title', 'Email Master')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('email_master');
    $isAlias = $tab === 'alias';
    $typeLabel = $tab === 'gmail' ? 'Gmail' : ($tab === 'email' ? 'Email' : 'Alias');
    $accountType = $tab === 'gmail' ? 'Gmail' : 'Email';

    $tabs = [
        'gmail' => ['label' => 'Gmail', 'icon' => 'bi-google',          'count' => $counts['gmail']],
        'email' => ['label' => 'Email', 'icon' => 'bi-envelope',        'count' => $counts['email']],
        'alias' => ['label' => 'Alias', 'icon' => 'bi-arrow-left-right', 'count' => $counts['alias']],
    ];

    $base = route('email-master.index');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Email Master</h1>
        <div class="page-subtitle">Company Gmail &amp; email accounts and their mailing aliases.</div>
    </div>
    @if($canEdit)
    <div class="d-flex gap-2 flex-wrap">
        @if($isAlias)
            <div class="dropdown">
                <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('email-aliases.export', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item" href="{{ route('email-aliases.export', ['format' => 'csv']) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
                </ul>
            </div>
            <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#importAliasModal">
                <i class="bi bi-upload"></i> Import
            </button>
            <a href="{{ route('email-aliases.create') }}" class="quick-action quick-action-primary">
                <i class="bi bi-plus-circle"></i> Add Alias
            </a>
        @else
            <div class="dropdown">
                <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                    <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('email-accounts.export', ['format' => 'xlsx', 'type' => $accountType]) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item" href="{{ route('email-accounts.export', ['format' => 'csv', 'type' => $accountType]) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
                </ul>
            </div>
            <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#importAccountModal">
                <i class="bi bi-upload"></i> Import
            </button>
            <a href="{{ route('email-accounts.create', ['type' => $accountType]) }}" class="quick-action quick-action-primary">
                <i class="bi bi-plus-circle"></i> Add {{ $accountType }}
            </a>
        @endif
    </div>
    @endif
</div>

{{-- Tab navigation --}}
<ul class="nav nav-pills gap-2 mb-3">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 {{ $tab === $key ? 'active' : '' }}" href="{{ $base . '?tab=' . $key }}">
                <i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                <span class="badge rounded-pill {{ $tab === $key ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ number_format($meta['count']) }}</span>
            </a>
        </li>
    @endforeach
</ul>

{{-- Status KPI cards (Gmail / Email only) --}}
@unless($isAlias)
    @php
        $totalActive    = $statusFilter === null;
        $activeActive   = $statusFilter === 'Active';
        $inactiveActive = $statusFilter === 'Inactive';
    @endphp
    <div class="stat-row mb-3" style="--stat-cols: 3;">
        <a class="stat-cell stat-link {{ $totalActive ? 'is-active' : '' }}"
           href="{{ $base . '?tab=' . $tab }}"
           style="--stat-color: #0d6efd;"
           title="Show all {{ $typeLabel }} accounts">
            <span class="stat-icon"><i class="bi bi-envelope-at"></i></span>
            <div class="stat-body">
                <div class="stat-label">Total {{ $typeLabel }}</div>
                <div class="stat-value">{{ number_format($typeCounts['total']) }}</div>
                <div class="stat-foot">All {{ $typeLabel }} accounts</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $activeActive ? 'is-active' : '' }}"
           href="{{ $base . '?tab=' . $tab . '&status=Active' }}"
           style="--stat-color: #10b981;"
           title="Show only Active {{ $typeLabel }} accounts">
            <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ number_format($typeCounts['active']) }}</div>
                <div class="stat-foot">{{ $typeCounts['total'] > 0 ? round($typeCounts['active'] / $typeCounts['total'] * 100) : 0 }}% of accounts</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $inactiveActive ? 'is-active' : '' }}"
           href="{{ $base . '?tab=' . $tab . '&status=Inactive' }}"
           style="--stat-color: #94a3b8;"
           title="Show only Inactive {{ $typeLabel }} accounts">
            <span class="stat-icon"><i class="bi bi-pause-circle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Inactive</div>
                <div class="stat-value">{{ number_format($typeCounts['inactive']) }}</div>
                <div class="stat-foot">{{ $typeCounts['total'] > 0 ? round($typeCounts['inactive'] / $typeCounts['total'] * 100) : 0 }}% of accounts</div>
            </div>
        </a>
    </div>
@endunless

{{-- Recent changes for the active tab (full width) --}}
<div class="card mb-3">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Changes &mdash; {{ $typeLabel }}</h6>
            @if($isAdmin)
                <a href="{{ route('activity-logs.index') }}" class="small text-decoration-none">View all</a>
            @endif
        </div>
        <div style="max-height: 320px; overflow-y: auto;">
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
                <p class="text-muted small text-center py-4 mb-0">No {{ strtolower($typeLabel) }} changes recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Search (full width) --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ $base }}" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if($statusFilter !== null)
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0 ps-0"
                           placeholder="{{ $isAlias ? 'Search main email or member address…' : 'Search name, address, department, username…' }}">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                @if($search !== '')
                    <a href="{{ $base . '?tab=' . $tab . ($statusFilter !== null ? '&status=' . $statusFilter : '') }}" class="btn btn-outline-secondary" title="Clear search"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- List (full width) --}}
@if($isAlias)
    @include('email_master.partials.alias_table')
@else
    @include('email_master.partials.account_table')
@endif

{{-- Import modals --}}
@if($canEdit)
    @unless($isAlias)
    <div class="modal fade" id="importAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('email-accounts.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="{{ $accountType }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-upload"></i> Import {{ $accountType }} Accounts</h5>
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
                            <a href="{{ route('email-accounts.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-file-earmark-excel"></i> Excel template</a>
                            <a href="{{ route('email-accounts.template', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-file-earmark-text"></i> CSV template</a>
                            <hr class="my-2">
                            <div class="mb-0">Rows without a <code>type</code> are imported as <strong>{{ $accountType }}</strong>. Passwords are stored encrypted.</div>
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
    @else
    <div class="modal fade" id="importAliasModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('email-aliases.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-upload"></i> Import Aliases</h5>
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
                            <a href="{{ route('email-aliases.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-file-earmark-excel"></i> Excel template</a>
                            <a href="{{ route('email-aliases.template', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-file-earmark-text"></i> CSV template</a>
                            <hr class="my-2">
                            <div class="mb-0">List member addresses in the <code>members</code> column, separated by commas.</div>
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
    @endunless

    {{-- Shared single-delete form --}}
    <form id="emSingleDeleteForm" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>
@endif
@endsection

@push('scripts')
<script>
(function () {
    // ---- Password reveal / copy (accounts) ----
    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.em-pw-toggle');
        if (toggle) {
            const wrap = toggle.closest('.em-pw');
            const dots = wrap.querySelector('.em-pw-dots');
            const icon = toggle.querySelector('i');
            const revealed = wrap.dataset.revealed === '1';
            if (revealed) {
                dots.textContent = '••••••••';
                wrap.dataset.revealed = '0';
                icon.className = 'bi bi-eye';
            } else {
                dots.textContent = wrap.dataset.pw || '';
                wrap.dataset.revealed = '1';
                icon.className = 'bi bi-eye-slash';
            }
            return;
        }
        const copy = e.target.closest('.em-pw-copy');
        if (copy) {
            const wrap = copy.closest('.em-pw');
            const value = wrap.dataset.pw || '';
            navigator.clipboard?.writeText(value);
            const icon = copy.querySelector('i');
            const prev = icon.className;
            icon.className = 'bi bi-check2 text-success';
            setTimeout(() => { icon.className = prev; }, 1200);
        }
    });

    // ---- Bulk select ----
    function refreshBulkToolbar() {
        const checks  = document.querySelectorAll('.em-row-check');
        const toolbar = document.getElementById('emBulkToolbar');
        const countEl = document.getElementById('emBulkCount');
        const selAll  = document.getElementById('emSelectAll');
        const selected = Array.from(checks).filter(c => c.checked).length;
        if (countEl) countEl.textContent = selected;
        if (toolbar) toolbar.classList.toggle('d-none', selected === 0);
        if (selAll) {
            selAll.checked = selected > 0 && selected === checks.length;
            selAll.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    document.addEventListener('change', (e) => {
        if (e.target.id === 'emSelectAll') {
            document.querySelectorAll('.em-row-check').forEach(c => { c.checked = e.target.checked; });
            refreshBulkToolbar();
        } else if (e.target.classList.contains('em-row-check')) {
            refreshBulkToolbar();
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('#emBulkClear')) {
            document.querySelectorAll('.em-row-check').forEach(c => { c.checked = false; });
            refreshBulkToolbar();
        }
    });

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#emBulkForm');
        if (!form) return;
        if (form.dataset.bulkConfirmed === '1') return;
        const selected = document.querySelectorAll('.em-row-check:checked').length;
        if (selected === 0) {
            e.preventDefault();
            alert('Select at least one row to delete.');
            return;
        }
        e.preventDefault();
        appConfirm({
            title: `Delete ${selected} record(s)?`,
            message: `You are about to permanently delete <strong>${selected}</strong> selected record(s).`,
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
        const btn = e.target.closest('.em-delete-single');
        if (!btn) return;
        const detail = btn.dataset.detail
            ? `<br><small class="text-muted">${appHtmlEscape(btn.dataset.detail)}</small>`
            : '';
        appConfirm({
            title: 'Delete this record?',
            message: `You are about to permanently delete <strong>${appHtmlEscape(btn.dataset.label)}</strong>.${detail}`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('emSingleDeleteForm');
            if (!form) return;
            form.action = btn.dataset.action;
            form.submit();
        });
    });

    refreshBulkToolbar();
})();
</script>

<style>
    /* Inactive accounts are dimmed, but stay readable on hover/selection. */
    tr.em-inactive > td { opacity: .5; }
    tr.em-inactive:hover > td { opacity: 1; }
    tr.em-inactive .badge { opacity: 1; }
</style>
@endpush
