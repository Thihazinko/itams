@extends('layouts.app')
@section('title', 'Add License / Contract')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add License / Contract</h1>
        <div class="page-subtitle">Register a software license, support agreement, or vendor contract.</div>
    </div>
    <a href="{{ route('licenses-contracts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('licenses-contracts.store') }}">
    @include('licenses_contracts._form')
</form>
@endsection
