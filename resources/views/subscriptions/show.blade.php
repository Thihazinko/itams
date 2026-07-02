@extends('layouts.app')
@section('title', $subscription->subscription_name)
@section('content')
@php
    $statusTone = $subscription->status === 'Active' ? 'success' : 'secondary';
    $rsBadge = match($subscription->renewal_status) {
        'Renewed'   => 'success',
        'Pending'   => 'warning',
        'Expired'   => 'danger',
        'Cancelled' => 'secondary',
        'Ongoing'   => 'info',
        default     => 'secondary',
    };

    $today = \Carbon\Carbon::today();
    $days  = $subscription->expire_date ? (int) $today->diffInDays($subscription->expire_date, false) : null;
    $daysTone = $subscription->renewal_status === 'Renewed'
        ? 'success'
        : ($days < 0 ? 'danger' : ($days <= 30 ? 'warning' : 'secondary'));

    // Matches the usage_duration accessor's cap: an expired, non-renewed
    // subscription is no longer "in use".
    $isExpiredUnrenewed = $subscription->expire_date
        && $subscription->expire_date->lt($today)
        && $subscription->renewal_status !== 'Renewed';
    $usedLabel = $isExpiredUnrenewed ? 'used' : 'in use';

    $prev = $subscription->previous_cost !== null ? (float) $subscription->previous_cost : null;
    $curr = $subscription->renewal_cost  !== null ? (float) $subscription->renewal_cost  : null;
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

    $renewalStatusMeta = [
        'draft'                   => ['Draft',           'secondary'],
        'pending_approval'        => ['Pending 1st',     'warning'],
        'first_approved'          => ['1st approved',    'info'],
        'pending_second_approval' => ['Pending 2nd',     'warning'],
        'approved'                => ['Both approved',   'primary'],
        'final_confirmed'         => ['Final confirmed', 'success'],
        'rejected'                => ['Rejected',        'danger'],
        'cancelled'               => ['Cancelled',       'secondary'],
    ];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $subscription->subscription_name }}</h1>
        <div class="page-subtitle">
            <span class="badge bg-info-subtle text-info-emphasis me-1">{{ $subscription->service_type }}</span>
            <span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis me-1">{{ $subscription->status }}</span>
            <span class="badge bg-{{ $rsBadge }}-subtle text-{{ $rsBadge }}-emphasis me-1">{{ $subscription->renewal_status }}</span>
            {{ $subscription->project_name }}
            @if($subscription->expire_date) &middot; expires {{ $subscription->expire_date->format('Y-m-d') }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('subscriptions.edit', $subscription) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
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
                    <dt class="col-sm-4 text-muted">Service Type</dt>
                    <dd class="col-sm-8">{{ $subscription->service_type }}</dd>

                    <dt class="col-sm-4 text-muted">Project</dt>
                    <dd class="col-sm-8">{{ $subscription->project_name }}</dd>

                    <dt class="col-sm-4 text-muted">Subscription</dt>
                    <dd class="col-sm-8">{{ $subscription->subscription_name }}</dd>

                    <dt class="col-sm-4 text-muted">Vendor</dt>
                    <dd class="col-sm-8">{{ $subscription->vendor_name ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Period</dt>
                    <dd class="col-sm-8">{{ $subscription->period ?: '—' }}</dd>
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
                    <dd class="col-sm-8">{{ $subscription->renewal_type }}</dd>

                    <dt class="col-sm-4 text-muted">Renewal Status</dt>
                    <dd class="col-sm-8"><span class="badge bg-{{ $rsBadge }}-subtle text-{{ $rsBadge }}-emphasis">{{ $subscription->renewal_status }}</span></dd>

                    <dt class="col-sm-4 text-muted">Start Using Date</dt>
                    <dd class="col-sm-8">
                        {{ $subscription->start_using_date?->format('Y-m-d') ?? '—' }}
                        @if($subscription->usage_duration)
                            <span class="badge bg-info-subtle text-info-emphasis ms-1" style="font-size:.65rem;">{{ $usedLabel }} {{ $subscription->usage_duration }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Previous Renewal Date</dt>
                    <dd class="col-sm-8">{{ $subscription->previous_renewal_date?->format('Y-m-d') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Expire Date</dt>
                    <dd class="col-sm-8">
                        @if($subscription->expire_date)
                            {{ $subscription->expire_date->format('Y-m-d') }}
                            @if($subscription->renewal_status === 'Renewed')
                                <span class="badge bg-success-subtle text-success-emphasis ms-1" style="font-size:.65rem;"><i class="bi bi-check2"></i> renewed</span>
                            @elseif($days !== null)
                                <span class="badge bg-{{ $daysTone }}-subtle text-{{ $daysTone }}-emphasis ms-1" style="font-size:.65rem;">{{ $days < 0 ? abs($days) . 'd overdue' : $days . 'd left' }}</span>
                            @endif
                        @else
                            <span class="text-muted">— <span class="small">(pay as you go)</span></span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Reminder Date</dt>
                    <dd class="col-sm-8">{{ $subscription->reminder_date?->format('Y-m-d') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-currency-exchange text-primary"></i>
                <strong>Pricing</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Currency</dt>
                    <dd class="col-sm-8">{{ $subscription->currency ?: 'MMK' }}</dd>

                    <dt class="col-sm-4 text-muted">Previous Cost</dt>
                    <dd class="col-sm-8">{{ $subscription->previous_cost !== null ? number_format((float) $subscription->previous_cost, 2) : '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Renewal Cost</dt>
                    <dd class="col-sm-8">{{ $subscription->renewal_cost !== null ? number_format((float) $subscription->renewal_cost, 2) : '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Price Change</dt>
                    <dd class="col-sm-8">
                        @if($priceChange)
                            <span class="badge bg-{{ $priceChange['tone'] }}-subtle text-{{ $priceChange['tone'] }}-emphasis d-inline-flex align-items-center gap-1">
                                <i class="bi {{ $priceChange['icon'] }}"></i> {{ $priceChange['label'] }}
                            </span>
                        @else
                            —
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
                <div style="white-space: pre-line;">{{ $subscription->remarks ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text text-primary"></i>
                <strong>Renewal History</strong>
                <span class="text-muted small ms-2">Purchase orders raised for this subscription.</span>
            </div>
            <div class="card-body">
                @forelse($subscription->renewals as $renewal)
                    @php($meta = $renewalStatusMeta[$renewal->status] ?? [$renewal->status, 'secondary'])
                    <div class="d-flex justify-content-between align-items-start gap-2 py-2 @if(!$loop->last) border-bottom border-light-subtle @endif">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                <a href="{{ route('subscriptions.renewals.show', $renewal) }}">{{ $renewal->po_number ?: 'P.O.' }}</a>
                            </div>
                            <div class="text-muted small">
                                {{ $renewal->po_date?->format('Y-m-d') ?? '—' }}
                                @if($renewal->total_amount !== null)
                                    &middot; {{ number_format((float) $renewal->total_amount, 2) }} {{ $renewal->currency ?: $subscription->currency }}
                                @endif
                            </div>
                        </div>
                        <span class="badge bg-{{ $meta[1] }}-subtle text-{{ $meta[1] }}-emphasis">{{ $meta[0] }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No renewals yet. Use the renew action on the list to raise a purchase order.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-body small">
                <div class="text-muted text-uppercase fw-semibold mb-2" style="font-size: .68rem; letter-spacing: .05em;">Audit</div>
                <div class="d-flex justify-content-between"><span class="text-muted">Modified by</span><span class="fw-semibold">{{ $subscription->modified_by ?: '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Last update</span><span>{{ $subscription->updated_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Created</span><span>{{ $subscription->created_at?->format('Y-m-d') ?? '—' }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
