@extends('layouts.app')
@section('title', 'Edit Alias')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Alias</h1>
        <div class="page-subtitle">Update {{ $alias->main_email }} and its members.</div>
    </div>
    <a href="{{ route('email-master.index', ['tab' => 'alias']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('email-aliases.update', $alias) }}">
    @method('PUT')
    @include('email_master.aliases._form')
</form>
@endsection
