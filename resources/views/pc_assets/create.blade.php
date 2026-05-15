@extends('layouts.app')
@section('title', 'Add PC')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add PC Asset</h1>
        <div class="page-subtitle">Register a new workstation or laptop in the PC Master.</div>
    </div>
    <a href="{{ route('pc-assets.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('pc-assets.store') }}">
    @include('pc_assets._form')
</form>
@endsection
