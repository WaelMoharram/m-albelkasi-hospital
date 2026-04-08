@extends('layouts.app')

@section('title', 'Register Patient')
@section('page_title', 'Register Patient')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('patients.index') }}">Patients</a></li>
    <li class="breadcrumb-item active">Register</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width: 800px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('patients.store') }}">
            @csrf
            @include('patients._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Register
                </button>
                <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
