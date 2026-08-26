@extends('layouts.app')

@section('title', __('Edit Role'))
@section('page_title', __('Edit Role'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">{{ __('Roles') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $role->name)) }}</div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf @method('PUT')
            @include('roles._form')
            <div class="d-flex gap-2 mt-4">
                @unless($role->is_protected)
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg ms-1"></i> {{ __('Update') }}
                </button>
                @endunless
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
