@extends('layouts.app')

@section('title', 'GCP Cost Breakdown')

@section('content')
@php
    $canEdit = auth()->user()->canEdit('gcp_costs');
    $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
    $periodLabel = $month ? $months[$month] . ' ' . $year : 'Full year ' . $year;
    // Yen shown with up to 6 decimals, trailing zeros trimmed (matches the sheet).
    $yen = function ($v) {
        $s = number_format((float) $v, 6, '.', ',');
        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Financial Management</h1>
        <div class="page-subtitle">Monthly Google Cloud Platform {{ $currency }} billing tables.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($canEdit)
        <a href="{{ route('gcp-costs.create', ['tab' => $tab]) }}" class="quick-action quick-action-primary"><i class="bi bi-plus-lg"></i> Add Breakdown</a>
        @endif
    </div>
</div>

@php
    $tabs = [
        'usd' => ['label' => 'USD', 'icon' => 'bi-currency-dollar', 'count' => $counts['usd']],
        'jpy' => ['label' => 'JPY', 'icon' => 'bi-currency-yen',    'count' => $counts['jpy']],
    ];
@endphp

{{-- Currency tabs (Account Types naming USD vs JPY) --}}
<ul class="nav nav-pills gap-2 mb-3">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('gcp-costs.index', ['tab' => $key, 'year' => $year, 'month' => $month]) }}">
                <i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                <span class="badge rounded-pill {{ $tab === $key ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ number_format($meta['count']) }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <h6 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-cloud text-primary"></i> GCP Cost Breakdown
        <span class="text-muted fw-normal small">· {{ $currency }} · {{ $periodLabel }}</span>
    </h6>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('gcp-costs.compare', ['tab' => $tab, 'year' => $year]) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-bar-chart-line"></i> Compare monthly costs
        </a>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Year">
                @foreach($years as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </select>
            <select name="month" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Month">
                <option value="">Whole year</option>
                @foreach($months as $mNum => $mName)<option value="{{ $mNum }}" @selected($month === $mNum)>{{ $mName }}</option>@endforeach
            </select>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">No</th>
                    <th>Period</th>
                    <th>Billing Account</th>
                    <th>Reported By</th>
                    <th class="text-end">Exchange Rate</th>
                    <th class="text-center">Projects</th>
                    @if($tab === 'jpy')
                    <th class="text-end">Total (¥)</th>
                    @else
                    <th class="text-end">Total ($)</th>
                    @endif
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdowns as $b)
                <tr>
                    <td class="text-muted">{{ $breakdowns->firstItem() + $loop->index }}</td>
                    <td>
                        <a href="{{ route('gcp-costs.show', $b) }}" class="fw-semibold text-decoration-none">{{ $b->periodLabel() }}</a>
                        <div class="small text-muted">{{ $b->periodRange() }}</div>
                    </td>
                    <td>{{ $b->billing_account_name ?: '—' }}</td>
                    <td>{{ $b->reported_by ?: '—' }}</td>
                    <td class="text-end">{{ $b->exchange_rate ? rtrim(rtrim(number_format((float) $b->exchange_rate, 6, '.', ','), '0'), '.') : '—' }}</td>
                    <td class="text-center">{{ $b->lines_count }}</td>
                    @if($tab === 'jpy')
                    <td class="text-end text-nowrap fw-semibold">¥ {{ $yen($b->total_cost_jpy ?? 0) }}</td>
                    @else
                    <td class="text-end text-nowrap fw-semibold">$ {{ number_format((float) ($b->total_cost_usd ?? 0), 6) }}</td>
                    @endif
                    <td class="text-end text-nowrap pe-3">
                        <a href="{{ route('gcp-costs.show', $b) }}" class="btn-icon-soft" title="View"><i class="bi bi-eye"></i></a>
                        @if($canEdit)
                        <a href="{{ route('gcp-costs.edit', $b) }}" class="btn-icon-soft" title="Edit"><i class="bi bi-pencil"></i></a>
                        <span class="form-check form-switch d-inline-flex align-items-center m-0 ms-1 me-1 align-middle"
                              title="Mail sending — send this month's breakdown by email">
                            <input type="checkbox" role="switch" class="form-check-input gcp-mail-check" style="cursor:pointer;"
                                   aria-label="Mail sending"
                                   data-action="{{ route('gcp-costs.mail', $b) }}"
                                   data-period="{{ $b->periodLabel() }}">
                        </span>
                        <form method="POST" action="{{ route('gcp-costs.duplicate', $b) }}" class="d-inline"
                              data-app-confirm
                              data-confirm-tone="success"
                              data-confirm-icon="bi-files"
                              data-confirm-title="Duplicate this breakdown?"
                              data-confirm-message="This copies <strong>{{ $b->periodLabel() }}</strong> — its billing account and all project lines — into a new breakdown for the next month."
                              data-confirm-note="You'll be taken to the copy to update the period and costs."
                              data-confirm-action="Duplicate">
                            @csrf
                            <button class="btn-icon-soft text-success" title="Duplicate for next month"><i class="bi bi-files"></i></button>
                        </form>
                        <form method="POST" action="{{ route('gcp-costs.destroy', $b) }}" class="d-inline"
                              onsubmit="return confirm('Delete the GCP breakdown for {{ $b->periodLabel() }}?')">
                            @csrf @method('DELETE')
                            <button class="btn-icon-soft text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-5">
                    <i class="bi bi-cloud-slash d-block mb-2" style="font-size:1.8rem;"></i>
                    No {{ $currency }} GCP cost breakdowns for {{ $periodLabel }}.@if($canEdit) Use <strong>Add Breakdown</strong> to create one.@endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($breakdowns->hasPages())
    <div class="card-footer bg-transparent">{{ $breakdowns->withQueryString()->links() }}</div>
    @endif
</div>

@if($canEdit)
{{-- Mail sending: one shared popup, populated per row (subject + free-typed To/Cc),
     sends that month's breakdown as a PDF attachment. --}}
<div class="modal fade" id="gcpMailModal" tabindex="-1" aria-labelledby="gcpMailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="gcpMailForm" action="">
                @csrf
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="gcpMailTitle">
                        <i class="bi bi-envelope-paper text-primary"></i> Send Cost Breakdown
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Emails the <strong>{{ $currency }}</strong> breakdown for
                        <strong class="gcp-mail-period">this month</strong> as a PDF attachment.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control gcp-mail-subject" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To <span class="text-danger">*</span></label>
                        <input type="text" name="to" class="form-control" required
                               placeholder="name@example.com, other@example.com">
                        <div class="form-text">Separate multiple addresses with commas.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Cc</label>
                        <input type="text" name="cc" class="form-control" placeholder="optional@example.com">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const modalEl = document.getElementById('gcpMailModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        const modal    = bootstrap.Modal.getOrCreateInstance(modalEl);
        const form     = document.getElementById('gcpMailForm');
        const periodEl = modalEl.querySelector('.gcp-mail-period');
        const subjectEl = modalEl.querySelector('.gcp-mail-subject');
        const currency = @json($currency);
        let activeCheck = null;

        // Ticking a row's "Mail sending" checkbox opens the popup for that month.
        document.querySelectorAll('.gcp-mail-check').forEach(function (chk) {
            chk.addEventListener('change', function () {
                if (!chk.checked) return;
                activeCheck = chk;
                const period = chk.dataset.period || '';
                form.setAttribute('action', chk.dataset.action);
                periodEl.textContent = period;
                subjectEl.value = 'GCP Cost Breakdown — ' + currency + ' — ' + period;
                modal.show();
            });
        });

        // Clear the checkbox whenever the popup closes (cancel, X, backdrop, or send).
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (activeCheck) { activeCheck.checked = false; activeCheck = null; }
        });
    })();
</script>
@endpush
