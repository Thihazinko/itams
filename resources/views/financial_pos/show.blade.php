@extends('layouts.app')

@section('title', $po->po_number)

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $canEdit = auth()->user()->canEdit('financial_management');
    $dec = fn ($cur) => $cur === 'USD' ? 2 : 0;
    $fmt = fn ($cur, $val) => $cur . ' ' . number_format((float) $val, $dec($cur));
    $received = $po->receiptsTotal();
    $remaining = $po->remainingAmount();
    $count = $po->receipts->count();

    if ($remaining < 0) {
        $remColor = '#dc3545'; $remLabel = 'Over-paid by ' . $fmt($po->currency, abs($remaining));
    } elseif ($remaining == 0 && $received > 0) {
        $remColor = '#10b981'; $remLabel = 'Fully received';
    } elseif ($received == 0) {
        $remColor = '#6c757d'; $remLabel = 'No receipts yet';
    } else {
        $remColor = '#f59e0b'; $remLabel = 'Outstanding';
    }
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $po->po_number }}</h1>
        <div class="page-subtitle">
            @php $sm = $po->sourceMeta(); @endphp
            <span class="badge {{ $sm['badge'] }} me-1"><i class="bi {{ $sm['icon'] }}"></i> {{ $sm['label'] }}</span>
            {{ $po->subject }}
            @if($po->vendor_name) &middot; {{ $po->vendor_name }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        @if($canEdit && $po->isManual())
            <a href="{{ route('financial-pos.edit', $po) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
        @endif
        <a href="{{ route('financial-pos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

{{-- Money summary band --}}
<div class="stat-row mb-3" style="--stat-cols: 3;">
    <div class="stat-cell" style="--stat-color: #0d6efd;">
        <span class="stat-icon"><i class="bi bi-receipt"></i></span>
        <div class="stat-body">
            <div class="stat-label">PO Amount</div>
            <div class="stat-value">{{ $fmt($po->currency, $po->total_amount) }}</div>
            <div class="stat-foot">{{ \App\Models\FinancialPo::CURRENCIES[$po->currency] ?? $po->currency }}</div>
        </div>
    </div>
    <div class="stat-cell" style="--stat-color: #10b981;">
        <span class="stat-icon"><i class="bi bi-cash-stack"></i></span>
        <div class="stat-body">
            <div class="stat-label">Received</div>
            <div class="stat-value">{{ $fmt($po->currency, $received) }}</div>
            <div class="stat-foot">{{ $count }} receipt{{ $count === 1 ? '' : 's' }}</div>
        </div>
    </div>
    <div class="stat-cell" style="--stat-color: {{ $remColor }};">
        <span class="stat-icon"><i class="bi bi-wallet2"></i></span>
        <div class="stat-body">
            <div class="stat-label">Remaining</div>
            <div class="stat-value">{{ $fmt($po->currency, $remaining) }}</div>
            <div class="stat-foot">{{ $remLabel }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-receipt text-primary"></i><strong>Purchase Order</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2 small">
                    <dt class="col-5 text-muted">PO Date</dt><dd class="col-7">{{ optional($po->po_date)->format('Y-m-d') }}</dd>
                    <dt class="col-5 text-muted">Vendor</dt><dd class="col-7">{{ $po->vendor_name ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Category</dt><dd class="col-7">{{ $po->category ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Currency</dt><dd class="col-7">{{ \App\Models\FinancialPo::CURRENCIES[$po->currency] ?? $po->currency }}</dd>
                    @if($po->notes)
                    <dt class="col-12 text-muted mt-2">Notes</dt><dd class="col-12" style="white-space: pre-line;">{{ $po->notes }}</dd>
                    @endif
                </dl>
                @if($po->isPayAsYouGo())
                    @if($canEdit)
                    <form method="POST" action="{{ route('financial-pos.update', $po) }}" class="mt-3">
                        @csrf @method('PUT')
                        <label class="form-label small mb-1 fw-semibold">Renewal Cost</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ $po->currency }}</span>
                            <input type="number" step="0.01" min="0" inputmode="decimal" name="total_amount"
                                   value="{{ old('total_amount', $po->total_amount) }}"
                                   class="form-control @error('total_amount') is-invalid @enderror" required>
                            <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
                            @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">
                            This pay-as-you-go PO is accrued automatically each month while the subscription stays active.
                            Adjust the Renewal Cost to match the actual usage charged this month.
                        </div>
                    </form>
                    @else
                    <div class="alert alert-info small mt-3 mb-0 d-flex gap-2">
                        <i class="bi bi-info-circle"></i>
                        <span>This is a <strong>pay-as-you-go</strong> PO, accrued automatically each month from its subscription.</span>
                    </div>
                    @endif
                @elseif($po->isManual())
                    <div class="alert alert-success small mt-3 mb-0 d-flex gap-2">
                        <i class="bi bi-cart-check"></i>
                        <span>This is a one-time purchase order entered by hand.@if($canEdit) Use <strong>Edit</strong> above to change its details.@endif</span>
                    </div>
                @else
                <div class="alert alert-info small mt-3 mb-0 d-flex gap-2">
                    <i class="bi bi-info-circle"></i>
                    <span>This PO is mirrored from <strong>{{ $po->sourceMeta()['label'] }}</strong> and is read-only here. You can still record its receipts below.</span>
                </div>
                @endif
            </div>
        </div>

        @if($canEdit)
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle text-primary"></i><strong>Record a Receipt</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('financial-pos.receipts.store', $po) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" value="{{ old('receipt_date', now()->format('Y-m-d')) }}" class="form-control form-control-sm @error('receipt_date') is-invalid @enderror" required>
                            @error('receipt_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Receipt No.</label>
                            <input type="text" name="receipt_number" value="{{ old('receipt_number') }}" class="form-control form-control-sm" placeholder="optional">
                        </div>
                        <div class="col-5">
                            <label class="form-label small mb-1">Currency <span class="text-danger">*</span></label>
                            <select name="currency" class="form-select form-select-sm @error('currency') is-invalid @enderror" required>
                                @foreach(\App\Models\FinancialPo::CURRENCIES as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency', $po->currency) === $code)>{{ $code }}</option>
                                @endforeach
                            </select>
                            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-7">
                            <label class="form-label small mb-1">Paid Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" inputmode="decimal" name="paid_amount" value="{{ old('paid_amount') }}" class="form-control form-control-sm @error('paid_amount') is-invalid @enderror" required>
                            @error('paid_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Payment Method</label>
                            <input type="text" name="payment_method" value="{{ old('payment_method') }}" class="form-control form-control-sm" placeholder="e.g. Bank transfer, Cash">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Receipt File <span class="text-muted">(PDF or image)</span></label>
                            <input type="file" name="file" class="form-control form-control-sm @error('file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Notes</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-3"><i class="bi bi-check2"></i> Add Receipt</button>
                </form>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-body small">
                <div class="text-muted text-uppercase fw-semibold mb-2" style="font-size: .68rem; letter-spacing: .05em;">Audit</div>
                <div class="d-flex justify-content-between"><span class="text-muted">Created by</span><span class="fw-semibold">{{ $po->created_by ?: '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Modified by</span><span>{{ $po->modified_by ?: '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Last update</span><span>{{ $po->updated_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Created</span><span>{{ $po->created_at?->format('Y-m-d') ?? '—' }}</span></div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-card-checklist text-primary"></i><strong>Receipts</strong>
                <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $count }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Receipt No.</th>
                            <th>Method</th>
                            <th class="text-end">Paid</th>
                            <th class="text-center">File</th>
                            @if($canEdit)<th class="text-end pe-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($po->receipts as $r)
                        <tr>
                            <td class="text-nowrap">{{ optional($r->receipt_date)->format('Y-m-d') }}</td>
                            <td>{{ $r->receipt_number ?: '—' }}</td>
                            <td>{{ $r->payment_method ?: '—' }}</td>
                            <td class="text-end text-nowrap fw-semibold">{{ $fmt($r->currency, $r->paid_amount) }}</td>
                            <td class="text-center text-nowrap">
                                @if($r->file_path)
                                    <a href="{{ route('financial-pos.receipts.file.download', [$po, $r]) }}" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Download attachment"><i class="bi bi-download"></i></a>
                                @endif
                                @if($canEdit)
                                    <form method="POST" action="{{ route('financial-pos.receipts.file.upload', [$po, $r]) }}" enctype="multipart/form-data" class="d-inline">
                                        @csrf
                                        <input type="file" name="file" class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp" onchange="this.form.submit()">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="this.previousElementSibling.click()" title="{{ $r->file_path ? 'Replace attachment' : 'Attach a file' }}">
                                            <i class="bi {{ $r->file_path ? 'bi-arrow-repeat' : 'bi-paperclip' }}"></i>
                                        </button>
                                    </form>
                                @elseif(! $r->file_path)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @if($canEdit)
                            <td class="text-end pe-3">
                                <form method="POST" action="{{ route('financial-pos.receipts.destroy', [$po, $r]) }}" onsubmit="return confirm('Delete this receipt?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete receipt"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="{{ $canEdit ? 6 : 5 }}" class="text-center text-muted py-5">
                            <i class="bi bi-receipt-cutoff d-block mb-2" style="font-size:1.8rem;"></i>
                            No receipts recorded yet.@if($canEdit) Use the form on the left to add one.@endif
                        </td></tr>
                        @endforelse
                    </tbody>
                    @if($po->receipts->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="3">Total Received</td>
                            <td class="text-end">{{ $fmt($po->currency, $received) }}</td>
                            <td colspan="{{ $canEdit ? 2 : 1 }}"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
