@extends('layouts.app')

@section('title', 'Edit GCP Cost Breakdown')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit GCP Cost Breakdown</h1>
        <div class="page-subtitle">{{ $breakdown->periodRange() }}</div>
    </div>
    <a href="{{ route('gcp-costs.show', $breakdown) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-cloud text-primary"></i><strong>Google Cloud Platform {{ $breakdown->currencyLabel() }} Account Billing</strong>
    </div>
    <div class="card-body">
        @include('gcp_cost_breakdowns._form', ['breakdown' => $breakdown, 'currency' => $breakdown->currencyLabel() === 'USD' ? 'USD' : 'JPY'])
    </div>
</div>
@endsection
