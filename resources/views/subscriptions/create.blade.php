@extends('layouts.app')
@section('title', 'Add Subscription')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Subscription</h1>
        <div class="page-subtitle">Register a domain, SSL, cloud service, or SaaS subscription.</div>
    </div>
    <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('subscriptions.store') }}">
    @include('subscriptions._form')
</form>
@endsection
