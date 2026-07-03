@extends('layouts.app')

@section('title', 'Add Purchase Order')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add Purchase Order</h1>
        <div class="page-subtitle">Record a one-time purchase, or link one to a registered Subscription or License &amp; Contract.</div>
    </div>
    <a href="{{ route('financial-pos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row">
    <div class="col-lg-9 col-xl-8">
        <div class="card">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-cart-plus text-success"></i><strong>Purchase Order Details</strong>
            </div>
            <div class="card-body">
                @include('financial_pos._form', ['po' => null])
            </div>
        </div>
    </div>
</div>
@endsection
