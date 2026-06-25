@extends('layouts.app')
@section('title', 'Add Repair Log')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Repair Log</h1>
        <div class="page-subtitle">Record a repair or maintenance entry for a PC.</div>
    </div>
    <a href="{{ route('repair-logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('repair-logs.store') }}">
    @include('repair_logs._form')
</form>
@endsection
