@extends('layouts.app')
@section('title', 'Edit Repair Log')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Repair Log</h1>
        <div class="page-subtitle">Update the repair entry for {{ $log->computer_id }}.</div>
    </div>
    <a href="{{ route('repair-logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('repair-logs.update', $log) }}">
    @method('PUT')
    @include('repair_logs._form')
</form>
@endsection
