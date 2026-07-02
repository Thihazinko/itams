@extends('layouts.app')
@section('title', 'Edit ' . $subscription->subscription_name)
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
@endphp
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $subscription->subscription_name }}</h1>
        <div class="page-subtitle">
            <span class="badge bg-info-subtle text-info-emphasis me-1">{{ $subscription->service_type }}</span>
            <span class="badge bg-{{ $rsBadge }}-subtle text-{{ $rsBadge }}-emphasis me-1">{{ $subscription->renewal_status }}</span>
            {{ $subscription->project_name }}
            @if($subscription->expire_date) &middot; expires {{ $subscription->expire_date->format('Y-m-d') }} @endif
        </div>
    </div>
    <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('subscriptions.update', $subscription) }}">
    @method('PUT')
    @include('subscriptions._form')
</form>
@endsection
