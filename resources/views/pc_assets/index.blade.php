@extends('layouts.app')

@section('title', 'PC Master')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('pc_assets');
    // "Disposed" view lists archived PCs (removed at budget year); the default
    // view lists the live fleet.
    $disposed = ($view ?? 'active') === 'disposed';
    $statusOrder = ['Active', 'Free', 'Damage', 'Retirement', 'Low Performance'];
    // Status totals still drive the KPI cards.
    $statusData = array_map(fn ($s) => (int) ($statusCounts[$s] ?? 0), $statusOrder);
    $kpiTotal     = array_sum($statusData);
    // Department breakdown drives the chart.
    $deptLabels = array_map(fn ($d) => trim((string) $d) === '' ? 'Unassigned' : $d, array_keys($departmentCounts ?? []));
    $deptData   = array_map('intval', array_values($departmentCounts ?? []));
    $chartTotal = array_sum($deptData);
    $kpiActive    = (int) ($statusCounts['Active'] ?? 0);
    $kpiFree      = (int) ($statusCounts['Free'] ?? 0);
    $kpiDamage    = (int) ($statusCounts['Damage'] ?? 0);
    $kpiRetire    = (int) ($statusCounts['Retirement'] ?? 0);
    $kpiLowPerf   = (int) ($statusCounts['Low Performance'] ?? 0);
    $kpiAttention = $kpiDamage + $kpiRetire + $kpiLowPerf;
    $pct = fn ($n) => $kpiTotal > 0 ? round($n / $kpiTotal * 100) : 0;

    $base = route('pc-assets.index');
    $totalKpi     = !request('status') && !request('attention') && !request('search') && !request('department');
    $activeKpi    = request('status') === 'Active' && !request('attention');
    $freeKpi      = request('status') === 'Free'   && !request('attention');
    $attentionKpi = (bool) request('attention');

    // Toggleable table columns (key => label) and which are shown by default.
    $pcColumns = [
        'computer_id'     => 'Computer ID',
        'hostname'        => 'Hostname',
        'employee'        => 'Employee',
        'status'          => 'Status',
        'department'      => 'Department',
        'location'        => 'Location',
        'brand'           => 'Brand / Model',
        'serial'          => 'Serial Number',
        'os'              => 'OS',
        'license'         => 'License Key',
        'expire'          => 'Expire Date',
        'cpu'             => 'CPU',
        'ram'             => 'RAM',
        'ssd'             => 'SSD',
        'hdd'             => 'HDD',
        'display'         => 'Display',
        'purchased'       => 'Purchased',
        'warranty_period' => 'Warranty Period',
        'warranty_status' => 'Warranty Status',
        'remarks'         => 'Remarks',
    ];
    $pcDefaultCols = ['computer_id', 'hostname', 'employee', 'status', 'department', 'location', 'brand', 'os', 'purchased'];
    // Helper: classes for a column cell — adds d-none when hidden by default.
    $colClass = fn ($key) => 'pc-col pc-col-' . $key . (in_array($key, $pcDefaultCols) ? '' : ' d-none');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">PC Master</h1>
        <div class="page-subtitle">Workstations, laptops, and assigned hardware across the company.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('pc-assets.export', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ route('pc-assets.export', ['format' => 'csv']) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
            </ul>
        </div>
        @if($canEdit)
        <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#importPcModal">
            <i class="bi bi-upload"></i> Import
        </button>
        @endif
        @if($canEdit)
        <a href="{{ route('pc-assets.create') }}" class="quick-action quick-action-primary">
            <i class="bi bi-plus-circle"></i> Add PC
        </a>
        @endif
    </div>
</div>

@include('pc_assets.partials.tabs')

<div class="modal fade" id="importPcModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('pc-assets.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import PC Assets</h5>
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
                        <a href="{{ route('pc-assets.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-file-earmark-excel"></i> Excel template
                        </a>
                        <a href="{{ route('pc-assets.template', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary mt-2">
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

<div id="pcContent" class="position-relative">
    <div id="pcLoadingOverlay" class="pc-loading-overlay d-none">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
    </div>

    @unless($disposed)
    <div class="stat-row mb-3">
        <a class="stat-cell stat-link {{ $totalKpi ? 'is-active' : '' }}"
           href="{{ $base }}"
           style="--stat-color: #0d6efd;"
           title="Show all PCs (clear filters)">
            <span class="stat-icon"><i class="bi bi-pc-display"></i></span>
            <div class="stat-body">
                <div class="stat-label">Total PCs</div>
                <div class="stat-value">{{ number_format($kpiTotal) }}</div>
                <div class="stat-foot">{{ request()->hasAny(['search','department','status','attention']) ? 'In current filter' : 'Across all departments' }}</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $activeKpi ? 'is-active' : '' }}"
           href="{{ $base . '?status=Active' }}"
           style="--stat-color: #10b981;"
           title="Show only Active PCs">
            <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ number_format($kpiActive) }}</div>
                <div class="stat-foot">{{ $pct($kpiActive) }}% of fleet</div>
                <div class="stat-bar"><span style="width: {{ $pct($kpiActive) }}%"></span></div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $freeKpi ? 'is-active' : '' }}"
           href="{{ $base . '?status=Free' }}"
           style="--stat-color: #8b5cf6;"
           title="Show only Free / Spare PCs">
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
           title="Show PCs needing attention (Damage, Retirement, Low Performance)">
            <span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Needs Attention</div>
                <div class="stat-value">{{ number_format($kpiAttention) }}</div>
                <div class="stat-foot">
                    <span title="Damage">{{ $kpiDamage }}<span class="opacity-50">D</span></span>
                    &middot;
                    <span title="Retirement">{{ $kpiRetire }}<span class="opacity-50">R</span></span>
                    &middot;
                    <span title="Low Performance">{{ $kpiLowPerf }}<span class="opacity-50">LP</span></span>
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
                        <h6 class="mb-0"><i class="bi bi-bar-chart-fill text-primary"></i> Department Breakdown</h6>
                        <span class="text-muted small">{{ $chartTotal }} PC{{ $chartTotal === 1 ? '' : 's' }}</span>
                    </div>
                    @if($chartTotal > 0)
                        <div style="position: relative; height: 200px;">
                            <canvas id="pcStatusChart"
                                    data-chart-labels='@json($deptLabels)'
                                    data-chart-data='@json($deptData)'
                                    data-chart-active='@json(request('department'))'></canvas>
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
                                    'created'  => ['bi-plus-circle',      'success'],
                                    'updated'  => ['bi-pencil-square',    'info'],
                                    'deleted'  => ['bi-trash',            'danger'],
                                    'imported' => ['bi-upload',           'warning'],
                                    'disposed' => ['bi-box-arrow-in-down', 'warning'],
                                    'restored' => ['bi-arrow-counterclockwise', 'success'],
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
                            <p class="text-muted small text-center py-4 mb-0">No PC asset changes recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endunless

    @if($disposed)
    <div class="alert alert-secondary d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-archive fs-5"></i>
        <div class="small">
            <strong>{{ number_format($assets->total()) }}</strong> PC{{ $assets->total() === 1 ? '' : 's' }}
            @if(request('budget_year')) disposed in budget year <strong>{{ request('budget_year') }}</strong>@else removed from service (budget-year disposal)@endif.
            They are kept for the record only and no longer count toward the active fleet.
            You can <strong>Restore</strong> one back to the active list or delete it permanently.
        </div>
    </div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" id="pcFilterForm" class="row g-2 align-items-center">
                @if($disposed)<input type="hidden" name="view" value="disposed">@endif
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Search Computer ID, hostname, employee, serial...">
                    </div>
                </div>
                @if($disposed)
                <div class="col-md-3">
                    <select name="budget_year" class="form-select">
                        <option value="">All Budget Years</option>
                        @foreach($budgetYears as $by)
                            <option value="{{ $by }}" @selected((string) request('budget_year') === (string) $by)>{{ $by }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($statusOrder as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        @foreach(\App\Models\PcAsset::DEPARTMENTS as $d)
                            <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    @if(request()->hasAny(['search','status','department','budget_year']))
                        <a href="{{ route('pc-assets.index', $disposed ? ['view' => 'disposed'] : []) }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
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
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="pcColReset" data-defaults='@json($pcDefaultCols)'>Reset</button>
                </li>
                @foreach($pcColumns as $key => $label)
                    <li>
                        <label class="dropdown-item d-flex align-items-center gap-2 px-2 rounded">
                            <input type="checkbox" class="form-check-input mt-0 pc-col-toggle" data-col="{{ $key }}" @checked(in_array($key, $pcDefaultCols))>
                            <span>{{ $label }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <form id="pcBulkForm" action="{{ route('pc-assets.bulk-destroy') }}" method="POST">
        @csrf @method('DELETE')

        @if($canEdit && !$disposed)
        <div id="pcBulkToolbar" class="card mb-2 d-none">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="small">
                    <i class="bi bi-check2-square text-primary"></i>
                    <strong id="pcBulkCount">0</strong> selected
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="pcBulkClear">Clear</button>
                    <button type="submit" class="btn btn-sm btn-danger" id="pcBulkDelete">
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
                            @if($canEdit && !$disposed)
                                <th style="width: 38px;">
                                    <input type="checkbox" id="pcSelectAll" class="form-check-input" title="Select all on page">
                                </th>
                            @endif
                            <th style="width: 60px;">No</th>
                            <th class="{{ $colClass('computer_id') }}">Computer ID</th>
                            <th class="{{ $colClass('hostname') }}">Hostname</th>
                            <th class="{{ $colClass('employee') }}">Employee</th>
                            <th class="{{ $colClass('status') }}">Status</th>
                            @if($disposed)
                                <th>Retired Date</th>
                                <th>Budget Year</th>
                                <th>Method</th>
                                <th>Reason</th>
                                <th>Disposed By</th>
                                <th>Approved By</th>
                            @endif
                            <th class="{{ $colClass('department') }}">Department</th>
                            <th class="{{ $colClass('location') }}">Location</th>
                            <th class="{{ $colClass('brand') }}">Brand / Model</th>
                            <th class="{{ $colClass('serial') }}">Serial Number</th>
                            <th class="{{ $colClass('os') }}">OS</th>
                            <th class="{{ $colClass('license') }}">License Key</th>
                            <th class="{{ $colClass('expire') }}">Expire Date</th>
                            <th class="{{ $colClass('cpu') }}">CPU</th>
                            <th class="{{ $colClass('ram') }}">RAM</th>
                            <th class="{{ $colClass('ssd') }}">SSD</th>
                            <th class="{{ $colClass('hdd') }}">HDD</th>
                            <th class="{{ $colClass('display') }}">Display</th>
                            <th class="{{ $colClass('purchased') }}">Purchased</th>
                            <th class="{{ $colClass('warranty_period') }}">Warranty Period</th>
                            <th class="{{ $colClass('warranty_status') }}">Warranty Status</th>
                            <th class="{{ $colClass('remarks') }}">Remarks</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assets as $i => $asset)
                            <tr>
                                @if($canEdit && !$disposed)
                                    <td>
                                        <input type="checkbox" name="ids[]" value="{{ $asset->id }}" class="form-check-input pc-row-check">
                                    </td>
                                @endif
                                <td class="text-muted small">{{ ($assets->firstItem() ?? 1) + $i }}</td>
                                <td class="{{ $colClass('computer_id') }}"><a href="{{ route('pc-assets.show', $asset) }}" class="fw-semibold text-decoration-none">{{ $asset->computer_id }}</a></td>
                                <td class="{{ $colClass('hostname') }}">{{ $asset->hostname }}</td>
                                <td class="{{ $colClass('employee') }}">{{ $asset->employee_name ?: '—' }}</td>
                                <td class="{{ $colClass('status') }}">
                                    @php $tone = match($asset->status) {
                                        'Active'          => 'success',
                                        'Free'            => 'info',
                                        'Damage'          => 'danger',
                                        'Retirement'      => 'secondary',
                                        'Low Performance' => 'warning',
                                        default           => 'secondary',
                                    }; @endphp
                                    <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis">{{ $asset->status }}</span>
                                </td>
                                @if($disposed)
                                    <td class="text-muted small text-nowrap">{{ $asset->retired_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $asset->budget_year ?: '—' }}</td>
                                    <td>{{ $asset->disposal_method ?: '—' }}</td>
                                    <td class="text-truncate" style="max-width: 220px;" title="{{ $asset->disposal_reason }}">{{ $asset->disposal_reason ?: '—' }}</td>
                                    <td>{{ $asset->disposed_by ?: '—' }}</td>
                                    <td>{{ $asset->approved_by ?: '—' }}</td>
                                @endif
                                <td class="{{ $colClass('department') }}">{{ $asset->department ?: '—' }}</td>
                                <td class="{{ $colClass('location') }}">
                                    @if($asset->location === 'WFH')
                                        <span class="badge bg-light text-dark border"><i class="bi bi-house-door"></i> WFH</span>
                                    @elseif($asset->location === 'Other')
                                        <span class="badge bg-light text-dark border"><i class="bi bi-geo-alt"></i> Other</span>
                                    @else
                                        <span class="badge bg-light text-dark border"><i class="bi bi-building"></i> Office</span>
                                    @endif
                                </td>
                                <td class="{{ $colClass('brand') }}">{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '—' }}</td>
                                <td class="{{ $colClass('serial') }}">{{ $asset->serial_number ?: '—' }}</td>
                                <td class="{{ $colClass('os') }}">{{ $asset->operating_system ?: '—' }}</td>
                                <td class="{{ $colClass('license') }}">{{ $asset->license_key ?: '—' }}</td>
                                <td class="{{ $colClass('expire') }} text-muted small">
                                    @if($asset->expire_permanent)
                                        <span class="badge bg-success-subtle text-success-emphasis">Permanent</span>
                                    @else
                                        {{ $asset->expire_date?->format('Y-m-d') ?? '—' }}
                                    @endif
                                </td>
                                <td class="{{ $colClass('cpu') }}">{{ $asset->cpu ?: '—' }}</td>
                                <td class="{{ $colClass('ram') }}">{{ $asset->ram ?: '—' }}</td>
                                <td class="{{ $colClass('ssd') }}">{{ $asset->ssd ?: '—' }}</td>
                                <td class="{{ $colClass('hdd') }}">{{ $asset->hdd ?: '—' }}</td>
                                <td class="{{ $colClass('display') }}">{{ $asset->display ?: '—' }}</td>
                                <td class="{{ $colClass('purchased') }} text-muted small">{{ $asset->purchased_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="{{ $colClass('warranty_period') }}">{{ $asset->warranty_period ?: '—' }}</td>
                                <td class="{{ $colClass('warranty_status') }}">
                                    @php $wtone = match($asset->warranty_status) {
                                        'In Warranty'   => 'success',
                                        'Expiring Soon' => 'warning',
                                        'Expired'       => 'danger',
                                        default         => 'secondary',
                                    }; @endphp
                                    <span class="badge bg-{{ $wtone }}-subtle text-{{ $wtone }}-emphasis">{{ $asset->warranty_status }}</span>
                                </td>
                                <td class="{{ $colClass('remarks') }} text-truncate" style="max-width: 220px;" title="{{ $asset->remarks }}">{{ $asset->remarks ?: '—' }}</td>
                                <td class="text-end text-nowrap pe-3">
                                    @php
                                        $pcDetail = trim(collect([
                                            trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')),
                                            $asset->employee_name,
                                            $asset->department,
                                        ])->filter()->implode(' · '));
                                    @endphp
                                    <a href="{{ route('pc-assets.show', $asset) }}" class="btn-icon-soft" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
                                    @if($disposed)
                                        @if($canEdit)
                                        <button type="button" class="btn-icon-soft text-success pc-restore-single"
                                                title="Restore to active list" aria-label="Restore"
                                                data-id="{{ $asset->id }}"
                                                data-label="{{ $asset->computer_id }}"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        <button type="button" class="btn-icon-soft text-danger pc-forcedelete-single"
                                                title="Delete permanently" aria-label="Delete permanently"
                                                data-id="{{ $asset->id }}"
                                                data-label="{{ $asset->computer_id }}"
                                                data-detail="{{ $pcDetail }}"><i class="bi bi-trash"></i></button>
                                        @endif
                                    @else
                                        <a href="{{ route('pc-assets.edit', $asset) }}" class="btn-icon-soft" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                        @if($canEdit)
                                        <button type="button" class="btn-icon-soft text-warning pc-dispose-single"
                                                title="Record disposal (budget-year removal)" aria-label="Dispose"
                                                data-id="{{ $asset->id }}"
                                                data-label="{{ $asset->computer_id }}"
                                                data-detail="{{ $pcDetail }}"><i class="bi bi-box-arrow-in-down"></i></button>
                                        <button type="button" class="btn-icon-soft text-danger pc-delete-single"
                                                title="Delete" aria-label="Delete"
                                                data-id="{{ $asset->id }}"
                                                data-label="{{ $asset->computer_id }}"
                                                data-detail="{{ $pcDetail }}"><i class="bi bi-trash"></i></button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="30" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        <div class="fw-semibold">{{ $disposed ? 'No disposed PCs' : 'No PC assets found' }}</div>
                                        <div class="small">
                                            @if(request()->hasAny(['search','status','department','budget_year']))
                                                Try clearing the filters or <a href="{{ route('pc-assets.index', $disposed ? ['view' => 'disposed'] : []) }}">view all</a>.
                                            @elseif($disposed)
                                                PCs you dispose (budget-year removal) will be listed here.
                                            @elseif($canEdit)
                                                <a href="{{ route('pc-assets.create') }}">Add the first PC</a> to get started.
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

    @if($canEdit)
    <form id="pcSingleDeleteForm" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>
    <form id="pcRestoreForm" method="POST" class="d-none">
        @csrf
    </form>
    <form id="pcForceDeleteForm" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>

    {{-- Dispose (budget-year removal) modal --}}
    <div class="modal fade" id="disposePcModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="disposePcForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-box-arrow-in-down text-warning"></i> Record PC Disposal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            You are recording <strong id="disposePcLabel"></strong> as disposed. It will move to the
                            <em>Disposed</em> list with the details below and leave the active fleet.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Retired / Removed date <span class="text-danger">*</span></label>
                                <input type="date" name="retired_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Budget year</label>
                                <input type="text" name="budget_year" class="form-control" value="{{ now()->format('Y') }}" placeholder="e.g. {{ now()->format('Y') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Disposal method</label>
                                <select name="disposal_method" class="form-select">
                                    <option value="">— Select —</option>
                                    @foreach(\App\Models\PcAsset::DISPOSAL_METHODS as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Approved by</label>
                                <input type="text" name="approved_by" class="form-control" placeholder="Who authorized removal">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Disposed by</label>
                                <input type="text" name="disposed_by" class="form-control" value="{{ auth()->user()->name }}" placeholder="Who carried it out">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason</label>
                                <textarea name="disposal_reason" class="form-control" rows="2" placeholder="e.g. beyond economic repair, too slow, end of life"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-box-arrow-in-down"></i> Record disposal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="mt-3">{{ $assets->links() }}</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    // Palette cycled across departments (department count is unbounded).
    const CHART_COLORS = [
        'rgba(13, 110, 253, 0.75)',  // blue
        'rgba(25, 135, 84, 0.75)',   // green
        'rgba(255, 193, 7, 0.85)',   // yellow
        'rgba(220, 53, 69, 0.75)',   // red
        'rgba(13, 202, 240, 0.75)',  // cyan
        'rgba(111, 66, 193, 0.75)',  // purple
        'rgba(253, 126, 20, 0.80)',  // orange
        'rgba(32, 201, 151, 0.75)',  // teal
        'rgba(214, 51, 132, 0.75)',  // pink
        'rgba(108, 117, 125, 0.75)', // gray
    ];

    let chartInstance = null;
    function initChart() {
        const ctx = document.getElementById('pcStatusChart');
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
        if (!ctx) return;
        const labels = JSON.parse(ctx.dataset.chartLabels || '[]');
        const data   = JSON.parse(ctx.dataset.chartData   || '[]');
        // When a department filter is active, emphasize it and dim the rest.
        const active = (ctx.dataset.chartActive && ctx.dataset.chartActive !== 'null')
            ? JSON.parse(ctx.dataset.chartActive) : null;
        const DIMMED = 'rgba(206, 212, 218, 0.55)';
        const barColors = labels.map((lbl, i) => {
            const base = CHART_COLORS[i % CHART_COLORS.length];
            return (active && lbl !== active) ? DIMMED : base;
        });
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'PCs',
                    data: data,
                    backgroundColor: barColors,
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
                                return `${ctx.parsed.y} PC(s) (${pct}%)`;
                            }
                        }
                    },
                },
            },
        });
    }

    function refreshBulkToolbar() {
        const checks  = document.querySelectorAll('.pc-row-check');
        const toolbar = document.getElementById('pcBulkToolbar');
        const countEl = document.getElementById('pcBulkCount');
        const selAll  = document.getElementById('pcSelectAll');
        const selected = Array.from(checks).filter(c => c.checked).length;
        if (countEl) countEl.textContent = selected;
        if (toolbar) toolbar.classList.toggle('d-none', selected === 0);
        if (selAll) {
            selAll.checked = selected > 0 && selected === checks.length;
            selAll.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    async function swap(url, { push = true } = {}) {
        const container = document.getElementById('pcContent');
        if (!container) { window.location.href = url; return; }
        const overlay = document.getElementById('pcLoadingOverlay');
        if (overlay) overlay.classList.remove('d-none');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('pcContent');
            if (!fresh) throw new Error('Missing #pcContent in response');
            container.replaceWith(fresh);
            if (push) history.pushState({ pcContent: true }, '', url);
            initChart();
            refreshBulkToolbar();
            restoreColumnPrefs();
        } catch (err) {
            if (overlay) overlay.classList.add('d-none');
            window.location.href = url;
        }
    }

    // ---- Event delegation: survives DOM swaps ----

    // Filter form submit
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#pcFilterForm');
        if (!form) return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form)).toString();
        const url = form.action ? form.action.split('?')[0] : window.location.pathname;
        swap(params ? `${url}?${params}` : url);
    });

    // Pagination links (a.page-link with hrefs inside #pcContent)
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#pcContent .pagination a.page-link');
        if (!link || link.getAttribute('href') === '#' || !link.href) return;
        e.preventDefault();
        swap(link.href);
    });

    // Clear-filters X button
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#pcContent a[href$="/pc-assets"]');
        if (!link || !link.querySelector('.bi-x-lg')) return;
        e.preventDefault();
        swap(link.href);
    });

    // KPI tile click → filter via AJAX swap
    document.addEventListener('click', (e) => {
        const tile = e.target.closest('#pcContent a.stat-link');
        if (!tile || !tile.href) return;
        e.preventDefault();
        swap(tile.href);
    });

    // Bulk select-all
    document.addEventListener('change', (e) => {
        if (e.target.id === 'pcSelectAll') {
            document.querySelectorAll('.pc-row-check').forEach(c => { c.checked = e.target.checked; });
            refreshBulkToolbar();
        } else if (e.target.classList.contains('pc-row-check')) {
            refreshBulkToolbar();
        }
    });

    // Bulk clear button
    document.addEventListener('click', (e) => {
        if (e.target.closest('#pcBulkClear')) {
            document.querySelectorAll('.pc-row-check').forEach(c => { c.checked = false; });
            refreshBulkToolbar();
        }
    });

    // Bulk delete confirm
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#pcBulkForm');
        if (!form) return;
        if (form.dataset.bulkConfirmed === '1') return;
        const selected = document.querySelectorAll('.pc-row-check:checked').length;
        if (selected === 0) {
            e.preventDefault();
            alert('Select at least one PC to delete.');
            return;
        }
        e.preventDefault();
        appConfirm({
            title: `Delete ${selected} PC asset(s)?`,
            message: `You are about to permanently delete <strong>${selected}</strong> selected PC asset record(s).`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete all',
        }).then((ok) => {
            if (!ok) return;
            form.dataset.bulkConfirmed = '1';
            form.submit();
        });
    });

    // Single delete (icon button in row)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.pc-delete-single');
        if (!btn) return;
        const id = btn.dataset.id;
        const label = btn.dataset.label;
        const detail = btn.dataset.detail
            ? `<br><small class="text-muted">${appHtmlEscape(btn.dataset.detail)}</small>`
            : '';
        appConfirm({
            title: 'Delete this PC asset?',
            message: `You are about to permanently delete <strong>${appHtmlEscape(label)}</strong>.${detail}`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('pcSingleDeleteForm');
            if (!form) return;
            form.action = `{{ url('pc-assets') }}/${id}`;
            form.submit();
        });
    });

    // Dispose single (budget-year removal) → open modal, target the form
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.pc-dispose-single');
        if (!btn) return;
        const form = document.getElementById('disposePcForm');
        const labelEl = document.getElementById('disposePcLabel');
        const modalEl = document.getElementById('disposePcModal');
        if (!form || !modalEl) return;
        form.action = `{{ url('pc-assets') }}/${btn.dataset.id}/dispose`;
        if (labelEl) labelEl.textContent = btn.dataset.label || 'this PC';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    // Restore a disposed PC back to the active list
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.pc-restore-single');
        if (!btn) return;
        appConfirm({
            title: 'Restore this PC?',
            message: `Restore <strong>${appHtmlEscape(btn.dataset.label)}</strong> to the active list.`,
            note: 'Its disposal details will be cleared and status set to Free.',
            confirmLabel: 'Restore',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('pcRestoreForm');
            if (!form) return;
            form.action = `{{ url('pc-assets') }}/${btn.dataset.id}/restore`;
            form.submit();
        });
    });

    // Permanently delete a disposed PC
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.pc-forcedelete-single');
        if (!btn) return;
        const detail = btn.dataset.detail
            ? `<br><small class="text-muted">${appHtmlEscape(btn.dataset.detail)}</small>`
            : '';
        appConfirm({
            title: 'Delete permanently?',
            message: `Permanently delete <strong>${appHtmlEscape(btn.dataset.label)}</strong>.${detail}`,
            note: 'This removes the record for good and cannot be undone.',
            confirmLabel: 'Delete permanently',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('pcForceDeleteForm');
            if (!form) return;
            form.action = `{{ url('pc-assets') }}/${btn.dataset.id}/force`;
            form.submit();
        });
    });

    // ---- Column show/hide chooser ----
    const COL_STORAGE_KEY = 'pcMasterColumns';

    // Apply each toggle's checked state to the matching table cells. The chooser
    // sits inside #pcContent, so an AJAX swap re-renders it with the blade
    // defaults — that's why swap() calls restoreColumnPrefs() (which re-reads
    // localStorage and re-checks the boxes) rather than applyColumnPrefs() alone.
    function applyColumnPrefs() {
        document.querySelectorAll('.pc-col-toggle').forEach((cb) => {
            document.querySelectorAll('.pc-col-' + cb.dataset.col).forEach((cell) => {
                cell.classList.toggle('d-none', !cb.checked);
            });
        });
    }

    function saveColumnPrefs() {
        const state = {};
        document.querySelectorAll('.pc-col-toggle').forEach((cb) => { state[cb.dataset.col] = cb.checked; });
        try { localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(state)); } catch (e) { /* ignore */ }
    }

    function restoreColumnPrefs() {
        let state = null;
        try { state = JSON.parse(localStorage.getItem(COL_STORAGE_KEY) || 'null'); } catch (e) { /* ignore */ }
        if (state) {
            document.querySelectorAll('.pc-col-toggle').forEach((cb) => {
                if (Object.prototype.hasOwnProperty.call(state, cb.dataset.col)) cb.checked = !!state[cb.dataset.col];
            });
        }
        applyColumnPrefs();
    }

    document.addEventListener('change', (e) => {
        if (!e.target.classList.contains('pc-col-toggle')) return;
        applyColumnPrefs();
        saveColumnPrefs();
    });

    document.addEventListener('click', (e) => {
        const reset = e.target.closest('#pcColReset');
        if (!reset) return;
        let defaults = [];
        try { defaults = JSON.parse(reset.dataset.defaults || '[]'); } catch (err) { /* ignore */ }
        document.querySelectorAll('.pc-col-toggle').forEach((cb) => { cb.checked = defaults.includes(cb.dataset.col); });
        applyColumnPrefs();
        saveColumnPrefs();
    });

    // Browser back/forward
    window.addEventListener('popstate', () => {
        swap(window.location.href, { push: false });
    });

    // Initial render
    initChart();
    refreshBulkToolbar();
    restoreColumnPrefs();
})();
</script>

<style>
    .pc-loading-overlay {
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
    [data-bs-theme="dark"] .pc-loading-overlay {
        background: rgba(15, 20, 27, 0.6);
    }
</style>
@endpush
