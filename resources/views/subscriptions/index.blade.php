@extends('layouts.app')

@section('title', 'Subscriptions')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('subscriptions');
    $kpiTotal    = (int) ($kpis['total']    ?? 0);
    $kpiActive   = (int) ($kpis['active']   ?? 0);
    $kpiExpiring = (int) ($kpis['expiring'] ?? 0);
    $kpiPending  = (int) ($kpis['pending']  ?? 0);
    $kpiExpired  = (int) ($kpis['expired']  ?? 0);
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Subscriptions</h1>
        <div class="page-subtitle">Recurring services &mdash; domains, SSL, cloud, and SaaS renewals.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('subscriptions.export', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ route('subscriptions.export', ['format' => 'csv']) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
            </ul>
        </div>
        @if($canEdit)
        <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#importSubModal">
            <i class="bi bi-upload"></i> Import
        </button>
        @endif
        @if($isAdmin)
        <a href="{{ route('subscriptions.create') }}" class="quick-action quick-action-primary">
            <i class="bi bi-plus-circle"></i> Add Subscription
        </a>
        @endif
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="importSubModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('subscriptions.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import Subscriptions</h5>
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
                        <a href="{{ route('subscriptions.template', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-file-earmark-excel"></i> Excel template
                        </a>
                        <a href="{{ route('subscriptions.template', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary mt-2">
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

<div id="subContent" class="position-relative">
    <div id="subLoadingOverlay" class="sub-loading-overlay d-none">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
    </div>

    @php
        $base = route('subscriptions.index');
        $activeKpi    = request('status') === 'Active' && !request('renewal_status') && !request('expiring_soon') && !request('overdue');
        $expiringKpi  = (bool) request('expiring_soon') && !request('renewal_status') && !request('overdue');
        $pendingKpi   = request('renewal_status') === 'Pending' && !request('expiring_soon') && !request('overdue');
        $overdueKpi   = (bool) request('overdue');
    @endphp
    <div class="stat-row mb-3" style="--stat-cols: 4;">
        <a class="stat-cell stat-link {{ $activeKpi ? 'is-active' : '' }}"
           href="{{ $base . '?status=Active' }}"
           style="--stat-color: #10b981;"
           title="Show only Active subscriptions">
            <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
            <div class="stat-body">
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ number_format($kpiActive) }}</div>
                <div class="stat-foot">of {{ number_format($kpiTotal) }} total subscriptions</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $expiringKpi ? 'is-active' : '' }}"
           href="{{ $base . '?expiring_soon=1' }}"
           style="--stat-color: #f59e0b;"
           title="Show subscriptions expiring within 30 days">
            <span class="stat-icon"><i class="bi bi-clock-history"></i></span>
            <div class="stat-body">
                <div class="stat-label">Expiring ≤30d</div>
                <div class="stat-value">{{ number_format($kpiExpiring) }}</div>
                <div class="stat-foot">Not yet renewed</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $pendingKpi ? 'is-active' : '' }}"
           href="{{ $base . '?renewal_status=Pending' }}"
           style="--stat-color: #6366f1;"
           title="Show subscriptions with Pending renewal status">
            <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="stat-body">
                <div class="stat-label">Pending Renewal</div>
                <div class="stat-value">{{ number_format($kpiPending) }}</div>
                <div class="stat-foot">Awaiting action</div>
            </div>
        </a>
        <a class="stat-cell stat-link {{ $overdueKpi ? 'is-active' : '' }}"
           href="{{ $base . '?overdue=1' }}"
           style="--stat-color: #ef4444;"
           title="Show expired and overdue subscriptions">
            <span class="stat-icon"><i class="bi bi-exclamation-octagon"></i></span>
            <div class="stat-body">
                <div class="stat-label">Expired / Overdue</div>
                <div class="stat-value">{{ number_format($kpiExpired) }}</div>
                <div class="stat-foot">Past expiry, no renewal</div>
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
                        @forelse($expiringSoon as $sub)
                            @php
                                $daysLeft = (int) \Carbon\Carbon::today()->diffInDays($sub->expire_date, false);
                                $tone = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 14 ? 'warning' : 'info');
                            @endphp
                            <div class="d-flex gap-2 py-2 border-bottom border-light-subtle align-items-center">
                                <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis align-self-start" style="min-width: 64px;">{{ $daysLeft }}d left</span>
                                <div class="flex-grow-1 small" style="line-height: 1.3; min-width: 0;">
                                    <div class="text-truncate" title="{{ $sub->subscription_name }} ({{ $sub->project_name }})">
                                        <strong>{{ $sub->subscription_name }}</strong> &middot; {{ $sub->project_name }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        {{ $sub->service_type }} &middot; expires {{ $sub->expire_date->format('Y-m-d') }}
                                    </div>
                                </div>
                                <a href="{{ route('subscriptions.edit', $sub) }}" class="btn-icon-soft" title="Edit"><i class="bi bi-pencil"></i></a>
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
                                    'renewed'  => ['bi-arrow-repeat',  'primary'],
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
                            <p class="text-muted small text-center py-4 mb-0">No subscription changes recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" id="subFilterForm" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Search project or name...">
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="text" name="service_type" value="{{ request('service_type') }}" class="form-control" placeholder="Service type (Domain, SSL...)">
                </div>
                <div class="col-md-2">
                    <select name="renewal_status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['Pending', 'Renewed', 'Expired', 'Cancelled'] as $s)
                            <option value="{{ $s }}" @selected(request('renewal_status') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-check d-flex align-items-center gap-2 mt-1">
                        <input type="checkbox" name="expiring_soon" value="1" class="form-check-input m-0" @checked(request('expiring_soon'))>
                        <span class="small">Expiring ≤30d</span>
                    </label>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    @if(request()->hasAny(['search','service_type','renewal_status','expiring_soon']))
                        <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <form id="subBulkForm" action="{{ route('subscriptions.bulk-destroy') }}" method="POST">
        @csrf @method('DELETE')

        @if($isAdmin)
        <div id="subBulkToolbar" class="card mb-2 d-none">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="small">
                    <i class="bi bi-check2-square text-primary"></i>
                    <strong id="subBulkCount">0</strong> selected
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="subBulkClear">Clear</button>
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
                                    <input type="checkbox" id="subSelectAll" class="form-check-input" title="Select all on page">
                                </th>
                            @endif
                            <th style="width: 60px;">No</th>
                            <th>Service / Name</th>
                            <th>Vendor</th>
                            <th>Expires</th>
                            <th>Days</th>
                            <th>In Use</th>
                            <th>Renewal</th>
                            <th class="text-end">Cost</th>
                            <th>Price Change</th>
                            <th>Renewal Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $i => $sub)
                            @php
                                $days = (int) \Carbon\Carbon::today()->diffInDays($sub->expire_date, false);
                                $daysTone = $sub->renewal_status === 'Renewed' ? 'success' :
                                    ($days < 0 ? 'danger' : ($days <= 7 ? 'danger' : ($days <= 30 ? 'warning' : 'secondary')));
                                $rsBadge = match($sub->renewal_status) {
                                    'Renewed'   => 'success',
                                    'Pending'   => 'warning',
                                    'Expired'   => 'danger',
                                    'Cancelled' => 'secondary',
                                    default     => 'secondary',
                                };
                                $statusTone = $sub->status === 'Active' ? 'success' : 'secondary';

                                $prev = $sub->previous_cost !== null ? (float) $sub->previous_cost : null;
                                $curr = $sub->renewal_cost  !== null ? (float) $sub->renewal_cost  : null;
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
                                        <input type="checkbox" name="ids[]" value="{{ $sub->id }}" class="form-check-input sub-row-check">
                                    </td>
                                @endif
                                <td class="text-muted small">{{ ($subscriptions->firstItem() ?? 1) + $i }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-info-subtle text-info-emphasis">{{ $sub->service_type }}</span>
                                        @if($sub->status !== 'Active')
                                            <span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis" title="Subscription is {{ $sub->status }}">{{ $sub->status }}</span>
                                        @endif
                                    </div>
                                    <div class="fw-semibold mt-1">{{ $sub->subscription_name }}</div>
                                    <div class="text-muted small">{{ $sub->project_name }}</div>
                                </td>
                                <td>{{ $sub->vendor_name ?: '—' }}</td>
                                <td class="text-nowrap small">{{ $sub->expire_date->format('Y-m-d') }}</td>
                                <td>
                                    @if($sub->renewal_status === 'Renewed')
                                        <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check2"></i> renewed</span>
                                    @else
                                        <span class="badge bg-{{ $daysTone }}-subtle text-{{ $daysTone }}-emphasis">
                                            {{ $days < 0 ? abs($days) . 'd overdue' : $days . 'd' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted small text-nowrap">
                                    @if($sub->usage_duration)
                                        {{ ($sub->expire_date && $sub->expire_date->lt(\Carbon\Carbon::today()) && $sub->renewal_status !== 'Renewed') ? 'used' : 'in use' }} {{ $sub->usage_duration }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $sub->renewal_type }}</td>
                                <td class="text-end">
                                    @if($sub->renewal_cost !== null)
                                        <span class="fw-semibold">{{ number_format((float) $sub->renewal_cost, 2) }}</span>
                                        <span class="text-muted small ms-1">{{ $sub->currency ?? 'MMK' }}</span>
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
                                <td><span class="badge bg-{{ $rsBadge }}-subtle text-{{ $rsBadge }}-emphasis">{{ $sub->renewal_status }}</span></td>
                                <td class="text-end text-nowrap pe-3">
                                    @php
                                        $subParts = [];
                                        if ($sub->service_type) $subParts[] = $sub->service_type;
                                        if ($sub->vendor_name)  $subParts[] = $sub->vendor_name;
                                        if ($sub->expire_date)  $subParts[] = 'expires ' . $sub->expire_date->format('Y-m-d');
                                        $subDetail = implode(' · ', $subParts);
                                        $activeRenewal = $activeRenewals[$sub->id] ?? null;
                                    @endphp
                                    @if($activeRenewal && $activeRenewal->status === 'draft')
                                        <a href="{{ route('purchase-orders.index') }}"
                                           class="btn-icon-soft text-secondary"
                                           title="Draft quotation — open Renewal Process to send to 1st approver" aria-label="Open draft P.O.">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                    @elseif($activeRenewal && $activeRenewal->status === 'pending_approval')
                                        <a href="{{ route('subscriptions.renewals.show', $activeRenewal) }}"
                                           class="btn-icon-soft text-warning"
                                           title="Awaiting 1st approval — view quotation" aria-label="View quotation">
                                            <i class="bi bi-hourglass-split"></i>
                                        </a>
                                    @elseif($activeRenewal && $activeRenewal->status === 'first_approved')
                                        <a href="{{ route('purchase-orders.index') }}"
                                           class="btn-icon-soft text-info"
                                           title="1st approved — open Renewal Process to send 2nd approver" aria-label="Open P.O. to send 2nd approver">
                                            <i class="bi bi-envelope-plus"></i>
                                        </a>
                                    @elseif($activeRenewal && $activeRenewal->status === 'pending_second_approval')
                                        <a href="{{ route('subscriptions.renewals.show', $activeRenewal) }}"
                                           class="btn-icon-soft text-warning"
                                           title="Awaiting 2nd approval — view quotation" aria-label="View quotation">
                                            <i class="bi bi-hourglass-split"></i>
                                        </a>
                                    @elseif($activeRenewal && $activeRenewal->status === 'approved' && $isAdmin)
                                        <button type="button" class="btn-icon-soft text-primary sub-final-confirm"
                                                title="Both approvers signed — final confirm renewal" aria-label="Final confirm"
                                                data-renewal-id="{{ $activeRenewal->id }}"
                                                data-po="{{ $activeRenewal->po_number }}"
                                                data-label="{{ $sub->subscription_name }}"
                                                data-detail="Approved by {{ $activeRenewal->approver_name }}{{ $activeRenewal->second_approver_name ? ' & ' . $activeRenewal->second_approver_name : '' }}">
                                            <i class="bi bi-patch-check-fill"></i>
                                        </button>
                                    @elseif($sub->renewal_status !== 'Renewed' && $isAdmin)
                                        <button type="button" class="btn-icon-soft sub-renew-po text-success"
                                                title="Renew Subscription" aria-label="Renew Subscription"
                                                data-id="{{ $sub->id }}"
                                                data-name="{{ $sub->subscription_name }}"
                                                data-service="{{ $sub->service_type }}"
                                                data-project="{{ $sub->project_name }}"
                                                data-vendor="{{ $sub->vendor_name }}"
                                                data-cost="{{ $sub->renewal_cost }}"
                                                data-currency="{{ $sub->currency ?: 'MMK' }}"
                                                data-expire="{{ optional($sub->expire_date)->format('Y-m-d') }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('subscriptions.show', $sub) }}" class="btn-icon-soft" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('subscriptions.edit', $sub) }}" class="btn-icon-soft" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    @if($isAdmin)
                                    <button type="button" class="btn-icon-soft text-danger sub-delete-single"
                                            title="Delete" aria-label="Delete"
                                            data-id="{{ $sub->id }}"
                                            data-label="{{ $sub->subscription_name }}"
                                            data-detail="{{ $subDetail }}"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 12 : 11 }}" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        <div class="fw-semibold">No subscriptions found</div>
                                        <div class="small">
                                            @if(request()->hasAny(['search','service_type','renewal_status','expiring_soon']))
                                                Try clearing the filters or <a href="{{ route('subscriptions.index') }}">view all</a>.
                                            @elseif($isAdmin)
                                                <a href="{{ route('subscriptions.create') }}">Add the first subscription</a> to get started.
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
    <form id="subFinalConfirmForm" method="POST" class="d-none">
        @csrf
    </form>

    <form id="subSingleDeleteForm" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>

    {{-- Renew Subscription modal --}}
    <div class="modal fade" id="subRenewPoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="subRenewPoForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-file-earmark-text"></i>
                            Renew Subscription
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small mb-3" id="subRenewPoContext">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="subRenewPoSummary">Subscription details will appear here.</span>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-2">P.O. details</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" required maxlength="255"
                                       placeholder="e.g. Annual renewal of SSL certificate">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reference</label>
                                <input type="text" name="reference" class="form-control" maxlength="255"
                                       placeholder="Internal ref. no.">
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-2">Vendor</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor company</label>
                                <input type="text" name="vendor_company" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Contact name</label>
                                <input type="text" name="vendor_name" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Phone / Email</label>
                                <input type="text" name="vendor_phone_email" class="form-control" maxlength="255">
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-2">Approver (quotation recipient)</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Pick a registered user (optional)</label>
                                <select name="approver_user_id" class="form-select" id="subRenewApproverSelect">
                                    <option value="">— External recipient (use email below) —</option>
                                    @foreach($approverChoices as $u)
                                        <option value="{{ $u->id }}"
                                                data-name="{{ $u->name }}"
                                                data-email="{{ $u->email }}">
                                            {{ $u->name }} &lt;{{ $u->email }}&gt;
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    Registered users will be required to log in; external recipients receive a signed link.
                                    A draft quotation is created here — no email is sent until you click the
                                    <i class="bi bi-envelope"></i> icon on the Renewal Process list.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Approver name <span class="text-danger">*</span></label>
                                <input type="text" name="approver_name" class="form-control" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Approver email <span class="text-danger">*</span></label>
                                <input type="email" name="approver_email" class="form-control" required maxlength="255">
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-2">Pricing</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required id="subRenewQty">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit price <span class="text-danger">*</span></label>
                                <input type="number" name="unit_price" class="form-control" min="0" step="0.01" required id="subRenewUnit">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select name="currency" class="form-select" required>
                                    @foreach(['MMK','JPY','USD'] as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Total</label>
                                <input type="text" class="form-control" id="subRenewTotal" readonly>
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="2000"
                                      placeholder="Optional note shown on the PO and in the email."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-file-earmark-plus"></i> Create draft P.O.
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="mt-3">{{ $subscriptions->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function refreshBulkToolbar() {
        const checks  = document.querySelectorAll('.sub-row-check');
        const toolbar = document.getElementById('subBulkToolbar');
        const countEl = document.getElementById('subBulkCount');
        const selAll  = document.getElementById('subSelectAll');
        const selected = Array.from(checks).filter(c => c.checked).length;
        if (countEl) countEl.textContent = selected;
        if (toolbar) toolbar.classList.toggle('d-none', selected === 0);
        if (selAll) {
            selAll.checked = selected > 0 && selected === checks.length;
            selAll.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    async function swap(url, { push = true } = {}) {
        const container = document.getElementById('subContent');
        if (!container) { window.location.href = url; return; }
        const overlay = document.getElementById('subLoadingOverlay');
        if (overlay) overlay.classList.remove('d-none');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('subContent');
            if (!fresh) throw new Error('Missing #subContent in response');
            container.replaceWith(fresh);
            if (push) history.pushState({ subContent: true }, '', url);
            refreshBulkToolbar();
        } catch (err) {
            if (overlay) overlay.classList.add('d-none');
            window.location.href = url;
        }
    }

    // Filter form
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#subFilterForm');
        if (!form) return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form)).toString();
        const url = form.action ? form.action.split('?')[0] : window.location.pathname;
        swap(params ? `${url}?${params}` : url);
    });

    // Pagination
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#subContent .pagination a.page-link');
        if (!link || link.getAttribute('href') === '#' || !link.href) return;
        e.preventDefault();
        swap(link.href);
    });

    // Clear-filters X
    document.addEventListener('click', (e) => {
        const link = e.target.closest('#subContent a[href$="/subscriptions"]');
        if (!link || !link.querySelector('.bi-x-lg')) return;
        e.preventDefault();
        swap(link.href);
    });

    // KPI tile click → filter via AJAX swap
    document.addEventListener('click', (e) => {
        const tile = e.target.closest('#subContent a.stat-link');
        if (!tile || !tile.href) return;
        e.preventDefault();
        swap(tile.href);
    });

    // Bulk select
    document.addEventListener('change', (e) => {
        if (e.target.id === 'subSelectAll') {
            document.querySelectorAll('.sub-row-check').forEach(c => { c.checked = e.target.checked; });
            refreshBulkToolbar();
        } else if (e.target.classList.contains('sub-row-check')) {
            refreshBulkToolbar();
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('#subBulkClear')) {
            document.querySelectorAll('.sub-row-check').forEach(c => { c.checked = false; });
            refreshBulkToolbar();
        }
    });

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#subBulkForm');
        if (!form) return;
        if (form.dataset.bulkConfirmed === '1') return;
        const selected = document.querySelectorAll('.sub-row-check:checked').length;
        if (selected === 0) {
            e.preventDefault();
            alert('Select at least one subscription to delete.');
            return;
        }
        e.preventDefault();
        appConfirm({
            title: `Delete ${selected} subscription(s)?`,
            message: `You are about to permanently delete <strong>${selected}</strong> selected subscription record(s).`,
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
        const btn = e.target.closest('.sub-delete-single');
        if (!btn) return;
        const detail = btn.dataset.detail
            ? `<br><small class="text-muted">${appHtmlEscape(btn.dataset.detail)}</small>`
            : '';
        appConfirm({
            title: 'Delete this subscription?',
            message: `You are about to permanently delete <strong>${appHtmlEscape(btn.dataset.label)}</strong>.${detail}`,
            note: 'This action cannot be undone.',
            confirmLabel: 'Delete',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('subSingleDeleteForm');
            if (!form) return;
            form.action = `{{ url('subscriptions') }}/${btn.dataset.id}`;
            form.submit();
        });
    });

    // Renew Subscription — open modal pre-filled
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.sub-renew-po');
        if (!btn) return;
        const form = document.getElementById('subRenewPoForm');
        const modalEl = document.getElementById('subRenewPoModal');
        if (!form || !modalEl || !window.bootstrap) return;

        form.action = `{{ url('subscriptions') }}/${btn.dataset.id}/renewals`;
        form.reset();

        // Pre-fill from the row
        const summary = `Renewing <strong>${appHtmlEscape(btn.dataset.name || '')}</strong>`
            + ` (${appHtmlEscape(btn.dataset.service || '—')} · ${appHtmlEscape(btn.dataset.project || '—')})`
            + ` &middot; expires <strong>${appHtmlEscape(btn.dataset.expire || '—')}</strong>`;
        const summaryEl = document.getElementById('subRenewPoSummary');
        if (summaryEl) summaryEl.innerHTML = summary;

        form.querySelector('[name="subject"]').value = `Renewal of ${btn.dataset.name || ''}`.trim();
        form.querySelector('[name="vendor_company"]').value = btn.dataset.vendor || '';
        if (btn.dataset.cost) form.querySelector('[name="unit_price"]').value = btn.dataset.cost;
        if (btn.dataset.currency) {
            const sel = form.querySelector('[name="currency"]');
            if (sel) sel.value = btn.dataset.currency;
        }
        recalcRenewTotal();

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    // Approver dropdown auto-fills name + email
    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'subRenewApproverSelect') {
            const opt = e.target.selectedOptions[0];
            const form = e.target.closest('form');
            if (!form || !opt) return;
            form.querySelector('[name="approver_name"]').value = opt.dataset.name || '';
            form.querySelector('[name="approver_email"]').value = opt.dataset.email || '';
        }
    });

    // Live total calculation
    function recalcRenewTotal() {
        const qty = parseFloat(document.getElementById('subRenewQty')?.value || '0');
        const unit = parseFloat(document.getElementById('subRenewUnit')?.value || '0');
        const out = document.getElementById('subRenewTotal');
        if (out) out.value = (qty * unit).toFixed(2);
    }
    document.addEventListener('input', (e) => {
        if (e.target && (e.target.id === 'subRenewQty' || e.target.id === 'subRenewUnit')) {
            recalcRenewTotal();
        }
    });

    // Final confirm renewal
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.sub-final-confirm');
        if (!btn) return;
        const detail = btn.dataset.detail
            ? `<br><small class="text-muted">${appHtmlEscape(btn.dataset.detail)}</small>`
            : '';
        appConfirm({
            tone: 'success',
            icon: 'bi-patch-check',
            title: 'Final confirm renewal?',
            message: `Finalise <strong>${appHtmlEscape(btn.dataset.label)}</strong> (${appHtmlEscape(btn.dataset.po)}).${detail}`,
            note: 'The expiry date will be extended and a confirmation email sent to all admins.',
            confirmLabel: 'Confirm renewal',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('subFinalConfirmForm');
            if (!form) return;
            form.action = `{{ url('subscriptions/renewals') }}/${btn.dataset.renewalId}/final-confirm`;
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
    .sub-loading-overlay {
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
    [data-bs-theme="dark"] .sub-loading-overlay {
        background: rgba(15, 20, 27, 0.6);
    }
</style>
@endpush
