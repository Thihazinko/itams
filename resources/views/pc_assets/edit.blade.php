@extends('layouts.app')
@section('title', 'Edit ' . $asset->computer_id)
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit {{ $asset->computer_id }}</h1>
        <div class="page-subtitle">{{ $asset->hostname }}@if($asset->employee_name) &middot; {{ $asset->employee_name }}@endif</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('pc-assets.show', $asset) }}" class="btn btn-outline-secondary"><i class="bi bi-eye"></i> View</a>
        <a href="{{ route('pc-assets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<form method="POST" action="{{ route('pc-assets.update', $asset) }}">
    @method('PUT')
    @include('pc_assets._form')
</form>
@endsection
