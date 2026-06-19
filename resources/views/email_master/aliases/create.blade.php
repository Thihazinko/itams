@extends('layouts.app')
@section('title', 'Add Alias')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Alias</h1>
        <div class="page-subtitle">Create a mailing alias and its member addresses.</div>
    </div>
    <a href="{{ route('email-master.index', ['tab' => 'alias']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('email-aliases.store') }}">
    @include('email_master.aliases._form')
</form>
@endsection
