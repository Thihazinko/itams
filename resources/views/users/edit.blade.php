@extends('layouts.app')
@section('title', 'Edit ' . $user->name)
@section('content')
@php $isMe = $user->id === auth()->id(); @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Edit {{ $user->name }}</h1>
        <div class="page-subtitle">
            <span class="badge bg-{{ $user->isAdmin() ? 'warning' : 'secondary' }}-subtle text-{{ $user->isAdmin() ? 'warning' : 'secondary' }}-emphasis me-1">
                <i class="bi {{ $user->isAdmin() ? 'bi-shield-lock' : 'bi-person' }}"></i> {{ ucfirst($user->role) }}
            </span>
            {{ $user->email }}
            @if($isMe) <span class="badge bg-primary-subtle text-primary-emphasis ms-1">You</span> @endif
        </div>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('users._form')
</form>
@endsection
