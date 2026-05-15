@extends('layouts.app')
@section('title', 'Add Device')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Device</h1>
        <div class="page-subtitle">Register a new peripheral, network device, or piece of equipment.</div>
    </div>
    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('devices.store') }}">
    @include('devices._form')
</form>
@endsection
