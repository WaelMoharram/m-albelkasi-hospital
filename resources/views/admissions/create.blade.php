@extends('layouts.app')

@section('title', __('New Admission'))
@section('page_title', __('New Admission'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">{{ __('Admissions') }}</a></li>
    <li class="breadcrumb-item active">{{ __('New') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admissions.store') }}">
            @csrf
            @include('admissions._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-clipboard2-plus ms-1"></i> {{ __('Admit Patient') }}
                </button>
                <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
