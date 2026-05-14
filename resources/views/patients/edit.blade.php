@extends('layouts.app')

@section('title', __('Edit Patient'))
@section('page_title', __('Edit Patient'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('patients.index') }}">{{ __('Patients') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Edit') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('patients.update', $patient) }}">
            @csrf @method('PUT')
            @include('patients._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg ms-1"></i> {{ __('Update') }}
                </button>
                <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
