@extends('layouts.app')

@section('title', 'GCP Cost Breakdown · ' . $breakdown->periodLabel())

@section('content')
@php
    $canEdit = auth()->user()->canEdit('gcp_costs');
    $yen = function ($v) {
        if ($v === null || $v === '') return '—';
        $s = number_format((float) $v, 6, '.', ',');
        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $usd = fn ($v) => ($v === null || $v === '') ? '—' : '$ ' . number_format((float) $v, 2);
    $total = $breakdown->totalCostJpy();
    $totalUsd = $breakdown->totalCostUsd();
    // JPY category shows only the yen column; USD category only the dollar column.
    $hasJpy = $breakdown->lines->contains(fn ($l) => $l->cost_jpy !== null);
    $hasUsd = ! $hasJpy;
    $costCols = 1;
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">GCP Cost Breakdown</h1>
        <div class="page-subtitle">Google Cloud Platform {{ $breakdown->currencyLabel() }} Account Billing · {{ $breakdown->periodRange() }}</div>
    </div>
    <div class="d-flex gap-2">
        @if($canEdit)
        <form method="POST" action="{{ route('gcp-costs.duplicate', $breakdown) }}" class="d-inline"
              data-app-confirm
              data-confirm-tone="success"
              data-confirm-icon="bi-files"
              data-confirm-title="Duplicate this breakdown?"
              data-confirm-message="This copies <strong>{{ $breakdown->periodLabel() }}</strong> — its billing account and all project lines — into a new breakdown for the next month."
              data-confirm-note="You'll be taken to the copy to update the period and costs."
              data-confirm-action="Duplicate">
            @csrf
            <button class="btn btn-outline-success"><i class="bi bi-files"></i> Duplicate for next month</button>
        </form>
        <a href="{{ route('gcp-costs.edit', $breakdown) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
        @endif
        <a href="{{ route('gcp-costs.index', ['tab' => $hasJpy ? 'jpy' : 'usd']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

{{-- Header metadata --}}
<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0 g-2 small">
            <dt class="col-sm-2 text-muted">Period</dt><dd class="col-sm-4">{{ $breakdown->periodRange() }}</dd>
            <dt class="col-sm-2 text-muted">Exchange Rate</dt><dd class="col-sm-4">{{ $breakdown->exchange_rate ? $yen($breakdown->exchange_rate) : '—' }}</dd>
            <dt class="col-sm-2 text-muted">Billing Account</dt><dd class="col-sm-4">{{ $breakdown->billing_account_name ?: '—' }}</dd>
            <dt class="col-sm-2 text-muted">Reported By</dt><dd class="col-sm-4">{{ $breakdown->reported_by ?: '—' }}</dd>
            @if($breakdown->notes)
            <dt class="col-sm-2 text-muted">Notes</dt><dd class="col-sm-10" style="white-space: pre-line;">{{ $breakdown->notes }}</dd>
            @endif
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-table text-primary"></i><strong>Project Cost Lines</strong>
        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $breakdown->lines->count() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:42px;">No</th>
                    <th>Project Name</th>
                    <th>Usage</th>
                    <th>Billing Account</th>
                    <th>Project ID</th>
                    <th>Usage Start</th>
                    <th>Usage End</th>
                    <th>Billing Card</th>
                    <th>Card Setting</th>
                    @if($hasJpy)<th class="text-end">Cost (¥)</th>@endif
                    @if($hasUsd)<th class="text-end">Cost ($)</th>@endif
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdown->lines as $line)
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $line->project_name ?: '—' }}</td>
                    <td class="small">{{ $line->usage ?: '—' }}</td>
                    <td class="small">{{ $line->billing_account_name ?: '—' }}</td>
                    <td class="small">{{ $line->project_id ?: '—' }}</td>
                    <td class="text-nowrap small">{{ optional($line->usage_start_date)->format('Y-m-d') ?: '—' }}</td>
                    <td class="text-nowrap small">{{ optional($line->usage_end_date)->format('Y-m-d') ?: '—' }}</td>
                    <td class="small">{{ $line->billing_card ?: '—' }}</td>
                    <td class="small">{{ $line->card_setting ?: '—' }}</td>
                    @if($hasJpy)<td class="text-end text-nowrap fw-semibold">{{ $line->cost_jpy !== null ? '¥ ' . $yen($line->cost_jpy) : '—' }}</td>@endif
                    @if($hasUsd)<td class="text-end text-nowrap fw-semibold">{{ $usd($line->cost_usd) }}</td>@endif
                    <td>
                        @if($line->status)
                            <span class="badge bg-danger-subtle text-danger-emphasis">{{ $line->status }}</span>
                        @else — @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ 10 + $costCols }}" class="text-center text-muted py-4">No project lines recorded.</td></tr>
                @endforelse
            </tbody>
            @if($breakdown->lines->isNotEmpty())
            <tfoot>
                <tr class="fw-bold table-light">
                    <td colspan="9" class="text-end">Total Amount</td>
                    @if($hasJpy)<td class="text-end text-nowrap">¥ {{ $yen($total) }}</td>@endif
                    @if($hasUsd)<td class="text-end text-nowrap">$ {{ number_format($totalUsd, 2) }}</td>@endif
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
