@extends('layouts.app')

@section('title', __('Add Invoice Category'))
@section('page_title', __('Add Invoice Category'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.invoice-categories.index') }}">{{ __('Invoice Categories') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Add') }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('catalog.invoice-categories.store') }}">
                    @csrf
                    @include('catalog.invoice-categories._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg ms-1"></i> {{ __('Save') }}
                        </button>
                        <a href="{{ route('catalog.invoice-categories.index') }}" class="btn btn-outline-secondary">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
