@extends('layouts.app')
@section('title', $device->item_name)
@section('content')
@php
    $statusTone = match($device->status) {
        'Active'     => 'success',
        'Free'       => 'info',
        'Damage'     => 'danger',
        'Retirement' => 'secondary',
        'Lost'       => 'warning',
        default      => 'secondary',
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $device->item_name }}</h1>
        <div class="page-subtitle">
            <span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis me-1">{{ $device->status }}</span>
            @if($device->serial_number) <code class="small">{{ $device->serial_number }}</code> @endif
            @if($device->qty > 1) &middot; Qty: <strong>{{ $device->qty }}</strong> @endif
            @if($device->location) &middot; {{ $device->location }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('devices.edit', $device) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-tag text-primary"></i>
                <strong>Identification</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Item Name</dt>
                    <dd class="col-sm-8">{{ $device->item_name }}</dd>

                    <dt class="col-sm-4 text-muted">Category</dt>
                    <dd class="col-sm-8">{{ $device->category ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Serial Number</dt>
                    <dd class="col-sm-8">
                        @if($device->serial_number)
                            <code>{{ $device->serial_number }}</code>
                        @else — @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Qty</dt>
                    <dd class="col-sm-8">{{ $device->qty }}</dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8"><span class="badge bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis">{{ $device->status }}</span></dd>

                    <dt class="col-sm-4 text-muted">Description</dt>
                    <dd class="col-sm-8" style="white-space: pre-line;">{{ $device->description ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-truck text-primary"></i>
                <strong>Logistics</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8">{{ $device->location ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Delivery Date</dt>
                    <dd class="col-sm-8">{{ $device->delivery_date?->format('Y-m-d') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Delivery Location</dt>
                    <dd class="col-sm-8">{{ $device->delivery_location ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-calendar-check text-primary"></i>
                <strong>Lifecycle</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Vendor</dt>
                    <dd class="col-sm-8">{{ $device->vendor ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Purchased Date</dt>
                    <dd class="col-sm-8">{{ $device->purchased_date?->format('Y-m-d') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Warranty</dt>
                    <dd class="col-sm-8">{{ $device->warranty ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Remark</dt>
                    <dd class="col-sm-8" style="white-space: pre-line;">{{ $device->remark ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body small">
                <div class="text-muted text-uppercase fw-semibold mb-2" style="font-size: .68rem; letter-spacing: .05em;">Audit</div>
                <div class="d-flex justify-content-between"><span class="text-muted">Modified by</span><span class="fw-semibold">{{ $device->modified_by ?: '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Last update</span><span>{{ $device->updated_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
                <div class="d-flex justify-content-between mt-1"><span class="text-muted">Created</span><span>{{ $device->created_at?->format('Y-m-d') ?? '—' }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
