@extends('layouts.app')
@section('title', $item->software_name)
@section('content')
@php
    $statusTone = match($item->status) {
        'Active'     => 'success',
        'Pending'    => 'warning',
        'Expired'    => 'danger',
        'Terminated' => 'secondary',
        default      => 'secondary',
    };

    $today = \Carbon\Carbon::today();
    $isPermanent = (bool) $item->expire_permanent;
    $days = $isPermanent ? null : (int) $today->diffInDays($item->expire_date, false);
    $isOverdue = !$isPermanent && $days < 0 && !in_array($item->status, ['Terminated']);

    $prev = $item->previous_cost !== null ? (float) $item->previous_cost : null;
    $curr = $item->renewal_cost  !== null ? (float) $item->renewal_cost  : null;
    $priceChange = null;
    if ($prev !== null && $curr !== null) {
        $diff = $curr - $prev;
        $pct = $prev > 0 ? ($diff / $prev) * 100 : null;
        if (abs($diff) < 0.005) {
            $priceChange = ['label' => 'No change', 'tone' => 'secondary', 'icon' => 'bi-dash'];
        } elseif ($diff > 0) {
            $priceChange = ['label' => 'Up ' . ($pct !== null ? '+' . number_format($pct, 1) . '%' : ''), 'tone' => 'danger', 'icon' => 'bi-arrow-up'];
        } else {
            $priceChange = ['label' => 'Down ' . ($pct !== null ? number_format($pct, 1) . '%' : ''), 'tone' => 'success', 'icon' => 'bi-arrow-down'];
        }
    }

    $canEdit = auth()->user()->canEdit('licenses_contracts');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $item->software_name }}</h1>
        <div class="page-subtitle">
            <span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis me-1">{{ $item->status }}</span>
            @if($item->vendor_name) {{ $item->vendor_name }} &middot; @endif
            @if($isPermanent)
                permanent (no expiry)
            @else
                expires {{ $item->expire_date->format('Y-m-d') }}
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('licenses-contracts.edit', $item) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('licenses-contracts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-tag text-primary"></i>
                <strong>General</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Software / Contract</dt>
                    <dd class="col-sm-8">{{ $item->software_name }}</dd>

                    <dt class="col-sm-4 text-muted">Vendor</dt>
                    <dd class="col-sm-8">{{ $item->vendor_name ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">License / Serial / Invoice</dt>
                    <dd class="col-sm-8" style="white-space: pre-line;">{{ $item->license_info ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat text-primary"></i>
                <strong>Renewal &amp; Lifecycle</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Renewal Type</dt>
                    <dd class="col-sm-8">{{ $item->renewal_type }}</dd>

                    <dt class="col-sm-4 text-muted">Start Using Date</dt>
                    <dd class="col-sm-8">
                        {{ $item->start_using_date?->format('Y-m-d') ?? '—' }}
                        @if($item->usage_duration)
                            @php($usedLabel = (!$isPermanent && $item->expire_date && $item->expire_date->lt($today)) ? 'used' : 'in use')
                            <span class="badge bg-info-subtle text-info-emphasis ms-1" style="font-size:.65rem;">{{ $usedLabel }} {{ $item->usage_duration }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Last Renewal</dt>
                    <dd class="col-sm-8">{{ $item->last_renewal_date?->format('Y-m-d') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Expire Date</dt>
                    <dd class="col-sm-8">
                        @if($isPermanent)
                            <span class="badge bg-success-subtle text-success-emphasis">Permanent</span>
                        @else
                            {{ $item->expire_date->format('Y-m-d') }}
                            @if($isOverdue)
                                <span class="badge bg-danger-subtle text-danger-emphasis ms-1" style="font-size:.65rem;">{{ abs($days) }}d overdue</span>
                            @elseif($item->status === 'Active' && $days <= 30)
                                <span class="badge bg-warning-subtle text-warning-emphasis ms-1" style="font-size:.65rem;">{{ $days }}d left</span>
                            @endif
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-sticky text-primary"></i>
                <strong>Remarks</strong>
            </div>
            <div class="card-body">
                <div style="white-space: pre-line;">{{ $item->remarks ?: '—' }}</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-paperclip text-primary"></i>
                <strong>Attachments</strong>
                <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $item->attachments->count() }}</span>
                <span class="text-muted small ms-auto">Contract, invoice, renewal quote, etc.</span>
            </div>
            <div class="card-body">
                @if($canEdit)
                <form method="POST" action="{{ route('licenses-contracts.attachments.store', $item) }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small mb-1">File(s) <span class="text-danger">*</span></label>
                            <input type="file" name="files[]" multiple required
                                   class="form-control form-control-sm @error('files') is-invalid @enderror @error('files.*') is-invalid @enderror"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                            @error('files')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @error('files.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Label <span class="text-muted">(optional)</span></label>
                            <input type="text" name="label" value="{{ old('label') }}" class="form-control form-control-sm" placeholder="e.g. Signed contract, Invoice">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> Upload</button>
                        </div>
                    </div>
                    <div class="form-text">PDF, image, Word, or Excel — up to 20 MB each. Select multiple to upload at once.</div>
                </form>
                @endif

                @forelse($item->attachments as $file)
                    <div class="d-flex align-items-center gap-2 py-2 @if(!$loop->last) border-bottom border-light-subtle @endif">
                        <i class="bi {{ $file->icon }} fs-4 text-secondary"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-truncate fw-semibold">{{ $file->display_name }}</div>
                            <div class="text-muted small">
                                @if($file->label && $file->original_name){{ $file->original_name }} &middot; @endif
                                @if($file->human_size){{ $file->human_size }} &middot; @endif
                                {{ $file->created_at?->format('Y-m-d') }}
                                @if($file->uploaded_by) &middot; {{ $file->uploaded_by }} @endif
                            </div>
                        </div>
                        <a href="{{ route('licenses-contracts.attachments.download', [$item, $file]) }}"
                           class="btn btn-sm btn-outline-secondary" title="Download"><i class="bi bi-download"></i></a>
                        @if($canEdit)
                        <form method="POST" action="{{ route('licenses-contracts.attachments.destroy', [$item, $file]) }}" class="d-inline"
                              data-app-confirm
                              data-confirm-title="Delete this attachment?"
                              data-confirm-message="Remove <strong>{{ e($file->display_name) }}</strong> from {{ e($item->software_name) }}."
                              data-confirm-label="{{ e($file->display_name) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete attachment"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </div>
                @empty
                    <p class="text-muted small mb-0 text-center py-2">
                        <i class="bi bi-paperclip d-block mb-1 fs-4 opacity-50"></i>
                        No files attached yet.@if($canEdit) Use the form above to add the contract, invoice, or renewal quote.@endif
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-currency-exchange text-primary"></i>
                <strong>Pricing</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-6 text-muted">Currency</dt>
                    <dd class="col-sm-6">{{ $item->currency ?: 'MMK' }}</dd>

                    @if($isPermanent)
                        <dt class="col-sm-6 text-muted">Cost</dt>
                        <dd class="col-sm-6">{{ $curr !== null ? number_format($curr, 2) : '—' }}</dd>
                    @else
                        <dt class="col-sm-6 text-muted">Previous Cost</dt>
                        <dd class="col-sm-6">{{ $prev !== null ? number_format($prev, 2) : '—' }}</dd>

                        <dt class="col-sm-6 text-muted">Renewal Cost</dt>
                        <dd class="col-sm-6">{{ $curr !== null ? number_format($curr, 2) : '—' }}</dd>

                        <dt class="col-sm-6 text-muted">Price Change</dt>
                        <dd class="col-sm-6">
                            @if($priceChange)
                                <span class="badge bg-{{ $priceChange['tone'] }}-subtle text-{{ $priceChange['tone'] }}-emphasis d-inline-flex align-items-center gap-1">
                                    <i class="bi {{ $priceChange['icon'] }}"></i> {{ trim($priceChange['label']) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-body small">
                <div class="text-muted text-uppercase fw-semibold mb-2" style="font-size: .68rem; letter-spacing: .05em;">Audit</div>
                <div class="d-flex justify-content-between"><span class="text-muted">Modified by</span><span class="fw-semibold">{{ $item->modified_by ?: '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Last update</span><span>{{ $item->updated_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Created</span><span>{{ $item->created_at?->format('Y-m-d') ?? '—' }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
