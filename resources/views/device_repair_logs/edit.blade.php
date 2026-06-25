@extends('layouts.app')
@section('title', 'Edit Device Repair Log')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Repair Log</h1>
        <div class="page-subtitle">Update the repair entry for {{ $log->device_label }}.</div>
    </div>
    <a href="{{ route('device-repair-logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('device-repair-logs.update', $log) }}">
    @method('PUT')
    @include('device_repair_logs._form')
</form>
@endsection
