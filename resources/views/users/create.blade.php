@extends('layouts.app')

@section('title', __('New User'))
@section('page_title', __('New User'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ __('Users') }}</a></li>
    <li class="breadcrumb-item active">{{ __('New') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('users.store') }}" autocomplete="off">
            @csrf
            @include('users._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus ms-1"></i> {{ __('Create User') }}
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
