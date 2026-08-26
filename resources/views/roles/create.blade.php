@extends('layouts.app')

@section('title', __('New Role'))
@section('page_title', __('New Role'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">{{ __('Roles') }}</a></li>
    <li class="breadcrumb-item active">{{ __('New') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            @include('roles._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg ms-1"></i> {{ __('Create Role') }}
                </button>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
