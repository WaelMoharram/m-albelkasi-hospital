@extends('layouts.app')

@section('title', __('Register Patient'))
@section('page_title', __('Register Patient'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('patients.index') }}">{{ __('Patients') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Register') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('patients.store') }}">
            @csrf
            @include('patients._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus ms-1"></i> {{ __('Register') }}
                </button>
                <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
