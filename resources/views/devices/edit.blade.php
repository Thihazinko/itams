@extends('layouts.app')
@section('title', 'Edit ' . $device->item_name)
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit {{ $device->item_name }}</h1>
        <div class="page-subtitle">
            @if($device->serial_number) <code class="small">{{ $device->serial_number }}</code> @endif
            @if($device->location) {{ $device->serial_number ? '·' : '' }} {{ $device->location }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('devices.show', $device) }}" class="btn btn-outline-secondary"><i class="bi bi-eye"></i> View</a>
        <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<form method="POST" action="{{ route('devices.update', $device) }}">
    @method('PUT')
    @include('devices._form')
</form>
@endsection
