@extends('layouts.app')
@section('title', 'Edit ' . $account->type . ' Account')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit {{ $account->type }} Account</h1>
        <div class="page-subtitle">Update {{ $account->address }}.</div>
    </div>
    <a href="{{ route('email-master.index', ['tab' => $account->type === 'Gmail' ? 'gmail' : 'email']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('email-accounts.update', $account) }}">
    @method('PUT')
    @include('email_master.accounts._form')
</form>
@endsection
