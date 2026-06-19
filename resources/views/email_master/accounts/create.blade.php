@extends('layouts.app')
@section('title', 'Add ' . $type . ' Account')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add {{ $type }} Account</h1>
        <div class="page-subtitle">Register a new {{ $type }} account.</div>
    </div>
    <a href="{{ route('email-master.index', ['tab' => $type === 'Gmail' ? 'gmail' : 'email']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('email-accounts.store') }}">
    @include('email_master.accounts._form')
</form>
@endsection
