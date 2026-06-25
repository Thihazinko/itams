@extends('layouts.app')
@section('title', 'Add Device Repair Log')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Repair Log</h1>
        <div class="page-subtitle">Record a repair or maintenance entry for a device.</div>
    </div>
    <a href="{{ route('device-repair-logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('device-repair-logs.store') }}">
    @include('device_repair_logs._form')
</form>
@endsection
