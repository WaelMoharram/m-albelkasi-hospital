@extends('layouts.app')

@section('title', 'Edit Patient')
@section('page_title', 'Edit Patient')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('patients.index') }}">Patients</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width: 800px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('patients.update', $patient) }}">
            @csrf @method('PUT')
            @include('patients._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update
                </button>
                <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
