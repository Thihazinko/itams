@extends('layouts.app')
@section('title', 'Edit ' . $item->software_name)
@section('content')
@php
    $statusTone = match($item->status) {
        'Active'     => 'success',
        'Pending'    => 'warning',
        'Expired'    => 'danger',
        'Terminated' => 'secondary',
        default      => 'secondary',
    };
@endphp
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $item->software_name }}</h1>
        <div class="page-subtitle">
            <span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis me-1">{{ $item->status }}</span>
            @if($item->vendor_name) {{ $item->vendor_name }} &middot; @endif
            @if($item->expire_permanent)
                permanent (no expiry)
            @else
                expires {{ $item->expire_date->format('Y-m-d') }}
            @endif
        </div>
    </div>
    <a href="{{ route('licenses-contracts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('licenses-contracts.update', $item) }}">
    @method('PUT')
    @include('licenses_contracts._form')
</form>
@endsection
