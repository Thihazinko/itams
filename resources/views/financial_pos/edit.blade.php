@extends('layouts.app')

@section('title', 'Edit ' . $po->po_number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Purchase Order</h1>
        <div class="page-subtitle">{{ $po->po_number }} &middot; {{ $po->subject }}</div>
    </div>
    <a href="{{ route('financial-pos.show', $po) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row">
    <div class="col-lg-9 col-xl-8">
        <div class="card">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-cart-plus text-success"></i><strong>One-Time Purchase Order</strong>
            </div>
            <div class="card-body">
                @include('financial_pos._form', ['po' => $po])
            </div>
        </div>
    </div>
</div>
@endsection
