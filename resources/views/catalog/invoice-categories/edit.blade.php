@extends('layouts.app')

@section('title', __('Edit Invoice Category'))
@section('page_title', __('Edit Invoice Category'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.invoice-categories.index') }}">{{ __('Invoice Categories') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('catalog.invoice-categories.update', $category) }}">
            @csrf @method('PUT')
            @include('catalog.invoice-categories._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg ms-1"></i> {{ __('Update') }}
                </button>
                <a href="{{ route('catalog.invoice-categories.index') }}" class="btn btn-outline-secondary">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
