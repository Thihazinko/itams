@extends('layouts.app')

@section('title', 'Financial Management')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('financial_management');

    // MMK/JPY are conventionally shown without decimals; USD with 2.
    $dec = fn ($cur) => $cur === 'USD' ? 2 : 0;
    $fmt = fn ($cur, $val) => $cur . ' ' . number_format((float) $val, $dec($cur));
    $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];

    // Per-currency visual identity for the budget band.
    $curMeta = [
        'MMK' => ['color' => '#0d6efd', 'icon' => 'bi-cash-coin'],
        'JPY' => ['color' => '#8b5cf6', 'icon' => 'bi-currency-yen'],
        'USD' => ['color' => '#10b981', 'icon' => 'bi-currency-dollar'],
    ];
    $periodLabel = $month ? $months[$month] . ' ' . $year : 'Full year ' . $year;
    $yearHasData = array_sum($yearTotals) > 0;
    $hasFilters = request()->hasAny(['search', 'currency', 'source']);
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Financial Management</h1>
        <div class="page-subtitle">Approved purchase orders, receipts, and monthly &amp; yearly budget usage.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('financial-pos.export', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ route('financial-pos.export', ['format' => 'csv']) }}"><i class="bi bi-file-earmark-text"></i> CSV (.csv)</a></li>
            </ul>
        </div>
    </div>
</div>

@php
    $fmBase = route('financial-pos.index');
    // Carry the selected period across tabs so both tables stay on the same period.
    $periodQs = array_filter(['year' => $year, 'month' => $month], fn ($v) => $v !== null && $v !== '');
@endphp
@include('financial_pos._tabs', ['active' => $tab, 'periodQs' => $periodQs])

@if($tab === 'receipts')
{{-- ============ RECEIPTS TAB ============ --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <h6 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-receipt text-primary"></i> Receipt History
        @if($poFilter)
            <span class="text-muted fw-normal small">· for PO <a href="{{ route('financial-pos.show', $poFilter) }}" class="fw-semibold text-decoration-none">{{ $poFilter->po_number }}</a></span>
        @else
            <span class="text-muted fw-normal small">· receipts dated in {{ $periodLabel }}</span>
        @endif
    </h6>
    <div class="d-flex gap-2 flex-wrap">
        @if(! $poFilter)
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="tab" value="receipts">
            <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Year">
                @foreach($years as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </select>
            <select name="month" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Month">
                <option value="">Whole year</option>
                @foreach($months as $mNum => $mName)<option value="{{ $mNum }}" @selected($month === $mNum)>{{ $mName }}</option>@endforeach
            </select>
        </form>
        @endif
        @if($canEdit)
        <button type="button" class="quick-action quick-action-primary" data-bs-toggle="modal" data-bs-target="#uploadReceiptModal">
            <i class="bi bi-upload"></i> Upload Receipt
        </button>
        @endif
    </div>
</div>

@if($poFilter)
<div class="alert alert-info d-flex flex-wrap align-items-center gap-2 py-2 px-3 mb-3">
    <i class="bi bi-funnel-fill"></i>
    <span class="small">Showing receipts linked to PO <strong>{{ $poFilter->po_number }}</strong>@if($poFilter->subject) — {{ \Illuminate\Support\Str::limit($poFilter->subject, 50) }}@endif.</span>
    <a href="{{ route('financial-pos.index', ['tab' => 'receipts']) }}" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-x-lg"></i> Show all receipts</a>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="receipts">
            @if($poFilter)
                <input type="hidden" name="po" value="{{ $poFilter->id }}">
            @else
                <input type="hidden" name="year" value="{{ $year }}">
                @if($month)<input type="hidden" name="month" value="{{ $month }}">@endif
            @endif
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="rsearch" value="{{ request('rsearch') }}" class="form-control border-start-0 ps-0" placeholder="Search PO number, vendor, receipt no...">
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                @if(request('rsearch'))
                    <a href="{{ $poFilter ? route('financial-pos.index', ['tab' => 'receipts', 'po' => $poFilter->id]) : ($fmBase . '?tab=receipts&year=' . $year . ($month ? '&month=' . $month : '')) }}" class="btn btn-outline-secondary" title="Clear"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">No</th>
                    <th>Receipt Date</th>
                    <th>PO Number</th>
                    <th>Vendor</th>
                    <th>Receipt No.</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">File</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $r)
                <tr>
                    <td class="text-muted">{{ $receipts->firstItem() + $loop->index }}</td>
                    <td class="text-nowrap">{{ optional($r->receipt_date)->format('Y-m-d') }}</td>
                    <td>
                        @if($r->financialPo)
                            <a href="{{ route('financial-pos.show', $r->financial_po_id) }}" class="fw-semibold text-decoration-none">{{ $r->financialPo->po_number }}</a>
                        @else <span class="text-muted">—</span> @endif
                    </td>
                    <td>{{ $r->financialPo?->vendor_name ?: '—' }}</td>
                    <td>{{ $r->receipt_number ?: '—' }}</td>
                    <td>{{ $r->payment_method ?: '—' }}</td>
                    <td class="text-end text-nowrap fw-semibold">{{ $fmt($r->currency, $r->paid_amount) }}</td>
                    <td class="text-center">
                        @if($r->file_path)
                            <a href="{{ route('financial-pos.receipts.file.download', [$r->financial_po_id, $r->id]) }}" class="btn-icon-soft" title="Download"><i class="bi bi-download"></i></a>
                        @else <span class="text-muted">—</span> @endif
                    </td>
                    <td class="text-end text-nowrap pe-3">
                        @if($canEdit)
                            <form method="POST" action="{{ route('financial-pos.receipts.destroy', [$r->financial_po_id, $r->id]) }}" class="d-inline"
                                  data-app-confirm
                                  data-confirm-tone="danger"
                                  data-confirm-icon="bi-trash-fill"
                                  data-confirm-title="Delete this receipt?"
                                  data-confirm-message="This removes receipt <strong>{{ e($r->receipt_number ?: '#' . $r->id) }}</strong> from PO <strong>{{ e(optional($r->financialPo)->po_number) }}</strong>."
                                  data-confirm-note="This action cannot be undone."
                                  data-confirm-action="Delete">
                                @csrf @method('DELETE')
                                <button class="btn-icon-soft text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        @else <span class="text-muted small">—</span> @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">
                    <i class="bi bi-receipt-cutoff d-block mb-2" style="font-size:1.8rem;"></i>
                    @if($poFilter)
                        No receipts recorded for PO <strong>{{ $poFilter->po_number }}</strong> yet.@if($canEdit) Use <strong>Upload Receipt</strong> to add one.@endif
                    @else
                        No receipts in {{ $periodLabel }}.@if($canEdit) Use <strong>Upload Receipt</strong> to add one.@endif
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($receipts->hasPages())
    <div class="card-footer bg-transparent">{{ $receipts->withQueryString()->links() }}</div>
    @endif
</div>

@if($canEdit)
{{-- Upload Receipt modal --}}
<div class="modal fade" id="uploadReceiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('financial-receipts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Upload Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Approved PO <span class="text-danger">*</span></label>
                            <select name="financial_po_id" class="form-select @error('financial_po_id') is-invalid @enderror" required
                                    onchange="var c=this.options[this.selectedIndex].dataset.currency; if(c){document.getElementById('rcCurrency').value=c;}">
                                <option value="">— Select a purchase order —</option>
                                @foreach($approvedPos as $opt)
                                    <option value="{{ $opt->id }}" data-currency="{{ $opt->currency }}" @selected(old('financial_po_id', $poFilter?->id) == $opt->id)>{{ $opt->po_number }} — {{ \Illuminate\Support\Str::limit($opt->subject, 40) }}{{ $opt->vendor_name ? ' (' . $opt->vendor_name . ')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('financial_po_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" value="{{ old('receipt_date', now()->format('Y-m-d')) }}" class="form-control @error('receipt_date') is-invalid @enderror" required>
                            @error('receipt_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Receipt No.</label>
                            <input type="text" name="receipt_number" value="{{ old('receipt_number') }}" class="form-control" placeholder="optional">
                        </div>
                        <div class="col-5">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="rcCurrency" class="form-select @error('currency') is-invalid @enderror" required>
                                @foreach($currencies as $cur)<option value="{{ $cur }}" @selected(old('currency') === $cur)>{{ $cur }}</option>@endforeach
                            </select>
                            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-7">
                            <label class="form-label">Paid Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" inputmode="decimal" name="paid_amount" value="{{ old('paid_amount') }}" class="form-control @error('paid_amount') is-invalid @enderror" required>
                            @error('paid_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Payment Method</label>
                            <input type="text" name="payment_method" value="{{ old('payment_method') }}" class="form-control" placeholder="e.g. Bank transfer, Cash">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Receipt File <span class="text-muted">(PDF or image)</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Upload Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@else
{{-- ============ PURCHASE ORDERS TAB ============ --}}

{{-- ===== Budget usage ===== --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <h6 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-graph-up-arrow text-primary"></i> Budget Usage
        <span class="text-muted fw-normal small">· {{ $periodLabel }} · amount paid by receipt date</span>
    </h6>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Year">
            @foreach($years as $y)
                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
            @endforeach
        </select>
        <select name="month" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Month">
            <option value="">Whole year</option>
            @foreach($months as $mNum => $mName)
                <option value="{{ $mNum }}" @selected($month === $mNum)>{{ $mName }}</option>
            @endforeach
        </select>
        <noscript><button class="btn btn-sm btn-primary">Apply</button></noscript>
    </form>
</div>

<div class="stat-row mb-3" style="--stat-cols: {{ count($currencies) }};">
    @foreach($currencies as $cur)
    @php $m = $curMeta[$cur] ?? ['color' => '#0d6efd', 'icon' => 'bi-cash']; @endphp
    <div class="stat-cell" style="--stat-color: {{ $m['color'] }};">
        <span class="stat-icon"><i class="bi {{ $m['icon'] }}"></i></span>
        <div class="stat-body">
            <div class="stat-label">{{ \App\Models\FinancialPo::CURRENCIES[$cur] }}</div>
            <div class="stat-value">{{ $fmt($cur, $periodTotals[$cur]) }}</div>
            <div class="stat-foot">
                @if($month)
                    {{ $year }} total: {{ $fmt($cur, $yearTotals[$cur]) }}
                @else
                    across {{ $year }}
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Monthly breakdown table + Budget Usage chart, side by side --}}
@php
    // Per-currency monthly series for the chart, mirroring the breakdown table.
    $chartSeries = [];
    foreach ($currencies as $cur) {
        $vals = [];
        for ($mm = 1; $mm <= 12; $mm++) {
            $vals[] = round((float) $monthly[$mm][$cur], $dec($cur));
        }
        $chartSeries[$cur] = $vals;
    }
    $chartColors = [];
    foreach ($currencies as $cur) {
        $chartColors[$cur] = $curMeta[$cur]['color'] ?? '#0d6efd';
    }
    // Only chart currencies that actually have spend this year — an empty flat
    // line for a zero-total currency reads as broken. Fall back to all if none.
    $chartCurrencies = array_values(array_filter($currencies, fn ($c) => ($yearTotals[$c] ?? 0) > 0));
    if (empty($chartCurrencies)) {
        $chartCurrencies = $currencies;
    }
@endphp
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-calendar3 text-primary"></i><strong>Monthly Breakdown</strong>
                <span class="text-muted small">{{ $year }}</span>
            </div>
            @if($yearHasData)
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-variant-numeric: tabular-nums;">
                    <thead>
                        <tr class="text-uppercase small text-muted" style="letter-spacing:.03em;">
                            <th class="border-0 ps-3">Month</th>
                            @foreach($currencies as $cur)
                                <th class="text-end border-0 {{ $loop->last ? 'pe-3' : '' }}">
                                    <span class="d-inline-flex align-items-center gap-1 justify-content-end">
                                        <span class="d-inline-block rounded-circle" style="width:8px;height:8px;background:{{ $curMeta[$cur]['color'] ?? '#0d6efd' }};"></span>
                                        {{ $cur }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $mNum => $mName)
                        <tr class="{{ $month === $mNum ? 'table-active fw-semibold' : '' }}">
                            <td class="ps-3 {{ $month === $mNum ? '' : 'text-body-secondary' }}">
                                @if($month === $mNum)<i class="bi bi-caret-right-fill text-primary small me-1"></i>@endif{{ $mName }}
                            </td>
                            @foreach($currencies as $cur)
                                <td class="text-end {{ $loop->last ? 'pe-3' : '' }} {{ $monthly[$mNum][$cur] > 0 ? '' : 'text-muted opacity-50' }}">
                                    {{ number_format($monthly[$mNum][$cur], $dec($cur)) }}
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold border-top">
                            <td class="ps-3">Year Total</td>
                            @foreach($currencies as $cur)
                                <td class="text-end {{ $loop->last ? 'pe-3' : '' }}" style="color:{{ $curMeta[$cur]['color'] ?? '#0d6efd' }};">
                                    {{ number_format($yearTotals[$cur], $dec($cur)) }}
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-calendar-x d-block mb-2" style="font-size:1.5rem;"></i>
                No POs dated in {{ $year }} yet.
            </div>
            @endif
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart-line text-primary"></i><strong>Budget Usage</strong>
                <span class="text-muted small">{{ $year }} · per currency</span>
            </div>
            <div class="card-body d-flex flex-column">
                @if($yearHasData)
                @php $single = count($chartCurrencies) === 1; @endphp
                <div class="row g-3 flex-grow-1">
                    @foreach($chartCurrencies as $cur)
                    <div class="{{ $single ? 'col-12' : 'col-md-6' }} d-flex">
                        <div class="border rounded-3 p-2 w-100 d-flex flex-column" style="border-left:3px solid {{ $chartColors[$cur] }} !important;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small fw-semibold d-flex align-items-center gap-1">
                                    <span class="d-inline-block rounded-circle" style="width:10px;height:10px;background:{{ $chartColors[$cur] }};"></span>
                                    {{ \App\Models\FinancialPo::CURRENCIES[$cur] ?? $cur }}
                                </span>
                                <span class="badge rounded-pill" style="background:{{ $chartColors[$cur] }}1a;color:{{ $chartColors[$cur] }};">
                                    {{ number_format($yearTotals[$cur], $dec($cur)) }}
                                </span>
                            </div>
                            <div class="position-relative flex-grow-1" style="min-height: {{ $single ? 240 : 150 }}px;">
                                <canvas class="budget-usage-chart"
                                        data-currency='@json($cur)'
                                        data-months='@json(array_values($months))'
                                        data-values='@json($chartSeries[$cur])'
                                        data-color='@json($chartColors[$cur])'></canvas>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted small text-center py-5 mb-0">No data to chart for {{ $year }}.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== PO register ===== --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-1">
    <h6 class="mb-0 d-flex align-items-center gap-2"><i class="bi bi-card-list text-primary"></i> Approved Purchase Orders</h6>
    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $poCount }}</span>
    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $periodLabel }}</span>
    @if($canEdit)
    <a href="{{ route('financial-pos.create') }}" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-plus-lg"></i> Add Purchase Order</a>
    @endif
</div>
<div class="text-muted small mb-2">
    Showing POs dated in <strong>{{ $periodLabel }}</strong>. Each PO's
    <em>amount</em> feeds the budget totals above.
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="year" value="{{ $year }}">
            @if($month)<input type="hidden" name="month" value="{{ $month }}">@endif
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Search PO number, subject, vendor, category...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="currency" class="form-select">
                    <option value="">All currencies</option>
                    @foreach($currencies as $cur)<option value="{{ $cur }}" @selected(request('currency') === $cur)>{{ \App\Models\FinancialPo::CURRENCIES[$cur] }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="source" class="form-select">
                    <option value="">All sources</option>
                    @foreach($sources as $src)
                        <option value="{{ $src }}" @selected(request('source') === $src)>{{ \App\Models\FinancialPo::SOURCES[$src]['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                @if($hasFilters)
                    <a href="{{ route('financial-pos.index', ['year' => $year, 'month' => $month]) }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">No</th>
                    <th>PO Number</th>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Vendor</th>
                    <th>Source</th>
                    <th class="text-end">Cost</th>
                    <th class="text-center">Receipt</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pos as $po)
                <tr>
                    <td class="text-muted">{{ $pos->firstItem() + $loop->index }}</td>
                    <td><a href="{{ route('financial-pos.show', $po) }}" class="fw-semibold text-decoration-none">{{ $po->po_number }}</a></td>
                    <td class="text-nowrap">{{ optional($po->po_date)->format('Y-m-d') }}</td>
                    <td>{{ $po->subject }}</td>
                    <td>{{ $po->vendor_name ?: '—' }}</td>
                    <td>
                        @php $sm = $po->sourceMeta(); @endphp
                        <span class="badge {{ $sm['badge'] }}"><i class="bi {{ $sm['icon'] }}"></i> {{ $sm['label'] }}</span>
                    </td>
                    <td class="text-end text-nowrap fw-semibold">{{ $fmt($po->currency, $po->total_amount) }}</td>
                    <td class="text-center text-nowrap">
                        @if(($po->receipts_count ?? 0) > 0)
                            <a href="{{ route('financial-pos.index', ['tab' => 'receipts', 'po' => $po->id]) }}" class="badge bg-success-subtle text-success-emphasis text-decoration-none" title="View this PO's {{ $po->receipts_count }} receipt(s)">
                                <i class="bi bi-paperclip"></i> {{ $po->receipts_count }}
                            </a>
                        @endif
                        @if($canEdit)
                        <form method="POST" action="{{ route('financial-pos.receipts.quick-upload', $po) }}" enctype="multipart/form-data" class="d-inline">
                            @csrf
                            <input type="file" name="file" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp" onchange="this.form.submit()">
                            <button type="button" class="btn-icon-soft" onclick="this.previousElementSibling.click()" title="Upload receipt"><i class="bi bi-upload"></i></button>
                        </form>
                        @elseif(($po->receipts_count ?? 0) === 0)
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap pe-3">
                        <a href="{{ route('financial-pos.show', $po) }}" class="btn-icon-soft" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
                        @if($canEdit)
                        <form method="POST" action="{{ route('financial-pos.destroy', $po) }}" class="d-inline"
                              data-app-confirm
                              data-confirm-tone="danger"
                              data-confirm-icon="bi-trash-fill"
                              data-confirm-title="Delete this purchase order?"
                              data-confirm-message="This removes PO <strong>{{ e($po->po_number) }}</strong> ({{ e($po->subject) }}) from the register{{ ($po->receipts_count ?? 0) > 0 ? ', along with its ' . $po->receipts_count . ' receipt' . ($po->receipts_count === 1 ? '' : 's') : '' }}."
                              data-confirm-note="This action cannot be undone."
                              data-confirm-action="Delete">
                            @csrf @method('DELETE')
                            <button class="btn-icon-soft text-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">
                    <i class="bi bi-inbox d-block mb-2" style="font-size:1.8rem;"></i>
                    @if($hasFilters)
                        No POs match your filters in {{ $periodLabel }}.
                    @else
                        No POs dated in {{ $periodLabel }}.
                        <div class="small mt-1">Try a different year or month above.</div>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pos->hasPages())
    <div class="card-footer bg-transparent">{{ $pos->withQueryString()->links() }}</div>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') return;

        document.querySelectorAll('.budget-usage-chart').forEach(function (el) {
            const cur = JSON.parse(el.dataset.currency || '""');
            const months = JSON.parse(el.dataset.months || '[]');
            const values = JSON.parse(el.dataset.values || '[]');
            const color = JSON.parse(el.dataset.color || '"#0d6efd"');

            new Chart(el, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: cur,
                        data: values,
                        borderColor: color,
                        backgroundColor: color + '33',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' },
                             ticks: { callback: function (v) { return Number(v).toLocaleString(); } } },
                        x: { grid: { display: false } },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return cur + ' ' + Number(ctx.parsed.y).toLocaleString();
                                }
                            }
                        },
                    },
                },
            });
        });
    })();
</script>
@if($tab === 'receipts' && $errors->any())
<script>
    // Re-open the Upload Receipt modal when its submission failed validation.
    document.addEventListener('DOMContentLoaded', function () {
        var m = document.getElementById('uploadReceiptModal');
        if (m && window.bootstrap) { new bootstrap.Modal(m).show(); }
    });
</script>
@endif
@endpush
