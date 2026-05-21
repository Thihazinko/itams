<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quotation {{ $renewal->po_number }} · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fb;
            background-image:
                radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.08), transparent 35%),
                radial-gradient(circle at 100% 100%, rgba(13, 110, 253, 0.06), transparent 40%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .quote-card { max-width: 880px; margin: 32px auto; }
        .meta-grid { display: grid; grid-template-columns: 150px 1fr 150px 1fr; gap: 8px 12px; font-size: 13px; }
        .meta-grid .label { color: #6b7280; }
        .totals-row { font-size: 18px; font-weight: 600; }
    </style>
</head>
<body>

<div class="container quote-card">

    @php
        $isSecond = ($approverStep ?? null) === \App\Models\SubscriptionRenewal::APPROVER_SECOND;
        $stageLabel = $isSecond ? '2nd-level approval' : '1st-level approval';
    @endphp

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="text-muted small">{{ config('app.name') }}</div>
            <h2 class="mb-0">Renewal Quotation</h2>
            <div class="small text-muted mt-1">
                <i class="bi bi-shield-check"></i> {{ $stageLabel }}
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-warning-subtle text-warning-emphasis fs-6">
                <i class="bi bi-hourglass-split"></i> Awaiting your approval
            </span>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <div class="fw-bold fs-5">{{ $renewal->po_number }}</div>
                    <div class="text-muted small">Issued {{ $renewal->po_date->format('d M Y') }}</div>
                </div>
                <a class="btn btn-outline-secondary"
                   href="{{ $isSigned
                        ? route('subscriptions.renewals.pdf', ['renewal' => $renewal->id, 'token' => $token])
                        : route('subscriptions.renewals.pdf', $renewal) }}"
                   target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>
            </div>

            <hr>

            <div class="meta-grid mb-3">
                <div class="label">Subject</div>
                <div><strong>{{ $renewal->subject }}</strong></div>
                <div class="label">Reference</div>
                <div>{{ $renewal->reference ?: '—' }}</div>

                <div class="label">Subscription</div>
                <div>
                    {{ $renewal->subscription->subscription_name }}
                    <span class="text-muted">({{ $renewal->subscription->service_type }})</span>
                </div>
                <div class="label">Project</div>
                <div>{{ $renewal->subscription->project_name }}</div>

                <div class="label">Vendor</div>
                <div>{{ $renewal->vendor_company ?: '—' }}</div>
                <div class="label">Contact</div>
                <div>
                    {{ $renewal->vendor_name ?: '—' }}
                    @if($renewal->vendor_phone_email)
                        <div class="text-muted small">{{ $renewal->vendor_phone_email }}</div>
                    @endif
                </div>

                <div class="label">Current expiry</div>
                <div>{{ optional($renewal->subscription->expire_date)->format('Y-m-d') }}</div>
                <div class="label">Renewal type</div>
                <div>{{ $renewal->subscription->renewal_type }}</div>

                <div class="label">Issued by</div>
                <div>{{ $renewal->created_by ?: '—' }}</div>
                <div class="label">1st approver</div>
                <div>
                    {{ $renewal->approver_name }}
                    <span class="text-muted small">&lt;{{ $renewal->approver_email }}&gt;</span>
                    @if($renewal->approved_at)
                        <span class="badge bg-success-subtle text-success-emphasis ms-1">
                            <i class="bi bi-check-circle"></i> approved {{ $renewal->approved_at->format('Y-m-d') }}
                        </span>
                    @endif
                </div>

                @if($renewal->second_approver_name)
                    <div class="label">2nd approver</div>
                    <div>
                        {{ $renewal->second_approver_name }}
                        <span class="text-muted small">&lt;{{ $renewal->second_approver_email }}&gt;</span>
                        @if($renewal->second_approved_at)
                            <span class="badge bg-success-subtle text-success-emphasis ms-1">
                                <i class="bi bi-check-circle"></i> approved {{ $renewal->second_approved_at->format('Y-m-d') }}
                            </span>
                        @endif
                    </div>
                    <div></div><div></div>
                @endif
            </div>

            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end" style="width: 80px;">Qty</th>
                        <th class="text-end" style="width: 140px;">Unit price</th>
                        <th class="text-end" style="width: 160px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $renewal->subscription->subscription_name }}</div>
                            <div class="text-muted small">{{ $renewal->subscription->service_type }} renewal</div>
                        </td>
                        <td class="text-end">{{ $renewal->quantity }}</td>
                        <td class="text-end">{{ number_format((float) $renewal->unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $renewal->total_amount, 2) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="3" class="text-end">Grand total</td>
                        <td class="text-end">{{ $renewal->currency }} {{ number_format((float) $renewal->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            @if($renewal->notes)
                <div class="alert alert-warning small">
                    <strong>Note:</strong> {{ $renewal->notes }}
                </div>
            @endif

            <hr>

            @php
                $canAct = ($isSecond && $renewal->status === \App\Models\SubscriptionRenewal::STATUS_PENDING_SECOND)
                       || (! $isSecond && $renewal->status === \App\Models\SubscriptionRenewal::STATUS_PENDING);
            @endphp

            @if($canAct)
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                    <form method="POST" action="{{ route('subscriptions.renewals.approve', $renewal) }}" class="d-inline">
                        @csrf
                        @if($isSigned)
                            <input type="hidden" name="token" value="{{ $token }}">
                        @endif
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Approve quotation
                        </button>
                    </form>
                </div>
            @else
                <div class="alert alert-secondary small mb-0">
                    <i class="bi bi-info-circle"></i>
                    This quotation is currently
                    <strong>{{ str_replace('_', ' ', $renewal->status) }}</strong>
                    and is not awaiting your action.
                </div>
            @endif

        </div>
    </div>

    <p class="text-center text-muted small mt-3 mb-4">
        @if($isSecond)
            After you approve, an administrator will finalise the renewal and send a confirmation email to all parties.
        @else
            After you approve, the issuer will send the quotation to the second approver for the next sign-off.
        @endif
    </p>
</div>

{{-- Reject modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('subscriptions.renewals.reject', $renewal) }}">
                @csrf
                @if($isSigned)
                    <input type="hidden" name="token" value="{{ $token }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Reject quotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Please tell us why this quotation is being rejected. The administrator will receive your reason.</p>
                    <textarea name="rejected_reason" class="form-control" rows="4" required maxlength="1000" placeholder="Reason for rejection..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject quotation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
