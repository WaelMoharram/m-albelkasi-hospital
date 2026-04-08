@extends('layouts.app')

@section('title', __('Edit User'))
@section('page_title', __('Edit User'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ __('Users') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:700px;">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary bg-opacity-10 p-2">
                <i class="bi bi-person text-primary"></i>
            </div>
            <div>
                <div class="fw-semibold">{{ $user->name }}</div>
                <div class="text-muted small">{{ $user->email }}</div>
            </div>
            <div class="me-auto">
                @if($user->is_active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('Inactive') }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('users.update', $user) }}" autocomplete="off">
            @csrf @method('PUT')
            @include('users._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg ms-1"></i> {{ __('Update') }}
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
