@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
@php
    $statusMeta = [
        'draft'                   => ['label' => 'Draft',              'tone' => 'secondary', 'icon' => 'bi-file-earmark'],
        'pending_approval'        => ['label' => 'Pending 1st',        'tone' => 'warning',   'icon' => 'bi-hourglass-split'],
        'first_approved'          => ['label' => '1st approved',       'tone' => 'info',      'icon' => 'bi-check-circle'],
        'pending_second_approval' => ['label' => 'Pending 2nd',        'tone' => 'warning',   'icon' => 'bi-hourglass-split'],
        'approved'                => ['label' => 'Both approved',      'tone' => 'primary',   'icon' => 'bi-check2-all'],
        'final_confirmed'         => ['label' => 'Final confirmed',    'tone' => 'success',   'icon' => 'bi-patch-check-fill'],
        'rejected'                => ['label' => 'Rejected',           'tone' => 'danger',    'icon' => 'bi-x-circle'],
        'cancelled'               => ['label' => 'Cancelled',          'tone' => 'secondary', 'icon' => 'bi-slash-circle'],
    ];
    $activeStatus = request('status');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Purchase Orders</h1>
        <div class="page-subtitle">All renewal P.O.s issued from the Subscriptions module.</div>
    </div>
    <a href="{{ route('subscriptions.index') }}" class="quick-action">
        <i class="bi bi-arrow-left"></i> Back to Subscriptions
    </a>
</div>

<div class="row g-3 mb-3">
    @foreach($statusMeta as $key => $meta)
        @php
            $isActive = $activeStatus === $key;
            $url = $isActive
                ? route('purchase-orders.index')
                : route('purchase-orders.index', ['status' => $key]);
        @endphp
        <div class="col">
            <a href="{{ $url }}"
               class="card text-decoration-none h-100 {{ $isActive ? 'border-' . $meta['tone'] : '' }}">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 small text-muted">
                        <i class="bi {{ $meta['icon'] }} text-{{ $meta['tone'] }}"></i>
                        {{ $meta['label'] }}
                    </div>
                    <div class="fs-3 fw-semibold mt-1">{{ $statusCounts[$key] ?? 0 }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0"
                           placeholder="Search P.O. number, subject, approver, vendor, subscription...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach($statusMeta as $key => $meta)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-link"><i class="bi bi-x-lg"></i> Clear</a>
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
                    <th>P.O. Number</th>
                    <th>Subscription</th>
                    <th>Subject</th>
                    <th>Approvers</th>
                    <th>Issued</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($renewals as $r)
                    @php $meta = $statusMeta[$r->status] ?? ['label' => $r->status, 'tone' => 'secondary', 'icon' => 'bi-question-circle']; @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $r->po_number }}</div>
                            <div class="text-muted small">{{ $r->po_date->format('Y-m-d') }}</div>
                        </td>
                        <td>
                            @if($r->subscription)
                                <div class="fw-semibold">{{ $r->subscription->subscription_name }}</div>
                                <div class="text-muted small">{{ $r->subscription->service_type }} · {{ $r->subscription->project_name }}</div>
                            @else
                                <span class="text-muted">(deleted subscription)</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $r->subject }}</div>
                            @if($r->reference)
                                <div class="text-muted small">Ref: {{ $r->reference }}</div>
                            @endif
                        </td>
                        <td class="small">
                            <div>
                                <span class="text-muted">1st:</span>
                                {{ $r->approver_name }}
                                @if($r->approved_at)
                                    <i class="bi bi-check-circle-fill text-success" title="Approved {{ $r->approved_at->format('Y-m-d H:i') }}"></i>
                                @endif
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $r->approver_email }}</div>
                            </div>
                            @if($r->second_approver_name)
                                <div class="mt-1">
                                    <span class="text-muted">2nd:</span>
                                    {{ $r->second_approver_name }}
                                    @if($r->second_approved_at)
                                        <i class="bi bi-check-circle-fill text-success" title="Approved {{ $r->second_approved_at->format('Y-m-d H:i') }}"></i>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $r->second_approver_email }}</div>
                                </div>
                            @endif
                        </td>
                        <td class="text-nowrap small">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                        <td class="text-end text-nowrap">
                            <span class="fw-semibold">{{ number_format((float) $r->total_amount, 2) }}</span>
                            <span class="text-muted small ms-1">{{ $r->currency }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $meta['tone'] }}-subtle text-{{ $meta['tone'] }}-emphasis">
                                <i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap pe-3">
                            <a href="{{ route('subscriptions.renewals.pdf', $r) }}" target="_blank"
                               class="btn-icon-soft text-secondary" title="Download PDF" aria-label="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <a href="{{ route('subscriptions.renewals.show', $r) }}"
                               class="btn-icon-soft" title="View quotation" aria-label="View">
                                <i class="bi bi-eye"></i>
                            </a>

                            @if($r->isEditable())
                                <a href="{{ route('purchase-orders.edit', $r) }}"
                                   class="btn-icon-soft text-primary" title="Edit P.O." aria-label="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif

                            {{-- Mail buttons --}}
                            @if($r->status === 'draft')
                                <button type="button"
                                        class="btn-icon-soft text-success po-send-first"
                                        title="Send to 1st approver" aria-label="Send to 1st approver"
                                        data-id="{{ $r->id }}"
                                        data-po="{{ $r->po_number }}"
                                        data-email="{{ $r->approver_email }}"
                                        data-name="{{ $r->approver_name }}">
                                    <i class="bi bi-envelope"></i>
                                </button>
                            @elseif($r->status === 'pending_approval')
                                <button type="button"
                                        class="btn-icon-soft text-warning po-send-first"
                                        title="Resend to 1st approver" aria-label="Resend to 1st approver"
                                        data-id="{{ $r->id }}"
                                        data-po="{{ $r->po_number }}"
                                        data-email="{{ $r->approver_email }}"
                                        data-name="{{ $r->approver_name }}"
                                        data-resend="1">
                                    <i class="bi bi-envelope-arrow-up"></i>
                                </button>
                            @elseif($r->status === 'first_approved')
                                <button type="button"
                                        class="btn-icon-soft text-success po-send-second"
                                        title="Send to 2nd approver" aria-label="Send to 2nd approver"
                                        data-id="{{ $r->id }}"
                                        data-po="{{ $r->po_number }}"
                                        data-second-id="{{ $r->second_approver_user_id }}"
                                        data-second-name="{{ $r->second_approver_name }}"
                                        data-second-email="{{ $r->second_approver_email }}">
                                    <i class="bi bi-envelope-plus"></i>
                                </button>
                            @elseif($r->status === 'pending_second_approval')
                                <button type="button"
                                        class="btn-icon-soft text-warning po-send-second"
                                        title="Resend to 2nd approver" aria-label="Resend to 2nd approver"
                                        data-id="{{ $r->id }}"
                                        data-po="{{ $r->po_number }}"
                                        data-second-id="{{ $r->second_approver_user_id }}"
                                        data-second-name="{{ $r->second_approver_name }}"
                                        data-second-email="{{ $r->second_approver_email }}"
                                        data-resend="1">
                                    <i class="bi bi-envelope-arrow-up"></i>
                                </button>
                            @elseif($r->status === 'approved')
                                <button type="button"
                                        class="btn-icon-soft text-primary po-final-confirm"
                                        title="Final confirm renewal" aria-label="Final confirm renewal"
                                        data-id="{{ $r->id }}"
                                        data-po="{{ $r->po_number }}"
                                        data-label="{{ $r->subscription?->subscription_name }}">
                                    <i class="bi bi-patch-check-fill"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                <div class="fw-semibold">No purchase orders found</div>
                                <div class="small">
                                    @if(request()->hasAny(['search', 'status']))
                                        Try clearing the filters or <a href="{{ route('purchase-orders.index') }}">view all</a>.
                                    @else
                                        Click <strong>Renew with PO</strong> on any subscription to issue a P.O.
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

<div class="mt-3">{{ $renewals->links() }}</div>

{{-- Hidden POST forms for mail-send / final-confirm --}}
<form id="poSendFirstForm" method="POST" class="d-none">@csrf</form>
<form id="poFinalConfirmForm" method="POST" class="d-none">@csrf</form>

{{-- Second-approver entry modal --}}
<div class="modal fade" id="poSecondApproverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="poSendSecondForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-envelope-plus"></i>
                        Send to 2nd approver
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3" id="poSecondApproverContext">
                        Sending P.O. <strong id="poSecondPoNumber"></strong> to the second approver.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pick a registered user (optional)</label>
                        <select name="second_approver_user_id" class="form-select" id="poSecondApproverSelect">
                            <option value="">— External recipient (use email below) —</option>
                            @foreach($approverChoices as $u)
                                <option value="{{ $u->id }}"
                                        data-name="{{ $u->name }}"
                                        data-email="{{ $u->email }}">
                                    {{ $u->name }} &lt;{{ $u->email }}&gt;
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Registered users will be required to log in; external recipients receive a signed link.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">2nd approver name <span class="text-danger">*</span></label>
                            <input type="text" name="second_approver_name" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">2nd approver email <span class="text-danger">*</span></label>
                            <input type="email" name="second_approver_email" class="form-control" required maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> <span id="poSecondSubmitLabel">Send to 2nd approver</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    // Send to 1st approver (draft → mail) / resend
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.po-send-first');
        if (!btn) return;
        const isResend = btn.dataset.resend === '1';
        appConfirm({
            tone: isResend ? 'warning' : 'success',
            icon: 'bi-envelope',
            title: (isResend ? 'Re-send to 1st approver?' : 'Send to 1st approver?'),
            message: `Email <strong>${appHtmlEscape(btn.dataset.po)}</strong> to <code>${appHtmlEscape(btn.dataset.email)}</code> (${appHtmlEscape(btn.dataset.name)}).`,
            confirmLabel: (isResend ? 'Re-send' : 'Send'),
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('poSendFirstForm');
            form.action = `{{ url('purchase-orders') }}/${btn.dataset.id}/send-first`;
            form.submit();
        });
    });

    // Send to 2nd approver — open modal
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.po-send-second');
        if (!btn) return;
        const form   = document.getElementById('poSendSecondForm');
        const modalEl = document.getElementById('poSecondApproverModal');
        if (!form || !modalEl || !window.bootstrap) return;

        form.action = `{{ url('purchase-orders') }}/${btn.dataset.id}/send-second`;
        form.reset();

        document.getElementById('poSecondPoNumber').textContent = btn.dataset.po || '';
        document.getElementById('poSecondSubmitLabel').textContent =
            btn.dataset.resend === '1' ? 'Re-send to 2nd approver' : 'Send to 2nd approver';

        // Pre-fill from existing values (resend case)
        const sel = form.querySelector('[name="second_approver_user_id"]');
        if (sel) sel.value = btn.dataset.secondId || '';
        form.querySelector('[name="second_approver_name"]').value  = btn.dataset.secondName  || '';
        form.querySelector('[name="second_approver_email"]').value = btn.dataset.secondEmail || '';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    // 2nd approver dropdown auto-fills name + email
    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'poSecondApproverSelect') {
            const opt = e.target.selectedOptions[0];
            const form = e.target.closest('form');
            if (!form || !opt) return;
            if (opt.dataset.name)  form.querySelector('[name="second_approver_name"]').value  = opt.dataset.name;
            if (opt.dataset.email) form.querySelector('[name="second_approver_email"]').value = opt.dataset.email;
        }
    });

    // Final confirm
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.po-final-confirm');
        if (!btn) return;
        appConfirm({
            tone: 'success',
            icon: 'bi-patch-check',
            title: 'Final confirm renewal?',
            message: `Finalise <strong>${appHtmlEscape(btn.dataset.label || '')}</strong> (${appHtmlEscape(btn.dataset.po)}).`,
            note: 'The expiry date will be extended and a confirmation email sent to all admins and both approvers.',
            confirmLabel: 'Confirm renewal',
        }).then((ok) => {
            if (!ok) return;
            const form = document.getElementById('poFinalConfirmForm');
            form.action = `{{ url('subscriptions/renewals') }}/${btn.dataset.id}/final-confirm`;
            form.submit();
        });
    });
})();
</script>
@endpush
