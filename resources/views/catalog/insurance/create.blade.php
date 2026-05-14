@extends('layouts.app')

@section('title', __('Add Insurance Company'))
@section('page_title', __('Add Insurance Company'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.insurance.index') }}">{{ __('Insurance Companies') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Add') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('catalog.insurance.store') }}">
            @csrf
            @include('catalog.insurance._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg ms-1"></i> {{ __('Save') }}
                </button>
                <a href="{{ route('catalog.insurance.index') }}" class="btn btn-outline-secondary">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
