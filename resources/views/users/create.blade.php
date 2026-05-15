@extends('layouts.app')
@section('title', 'Add User')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add User</h1>
        <div class="page-subtitle">Create a new account and grant module access.</div>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
    @include('users._form')
</form>
@endsection
