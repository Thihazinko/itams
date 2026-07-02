@extends('layouts.app')

@section('title', 'Add GCP Cost Breakdown')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add GCP Cost Breakdown</h1>
        <div class="page-subtitle">Record a monthly Google Cloud Platform billing table.</div>
    </div>
    <a href="{{ route('gcp-costs.index', ['tab' => strtolower($currency)]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-cloud text-primary"></i><strong>Google Cloud Platform {{ $currency }} Account Billing</strong>
    </div>
    <div class="card-body">
        @include('gcp_cost_breakdowns._form', ['breakdown' => null, 'currency' => $currency])
    </div>
</div>
@endsection
