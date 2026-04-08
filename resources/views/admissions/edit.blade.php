@extends('layouts.app')

@section('title', 'Edit Admission')
@section('page_title', 'Edit Admission')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">Admissions</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admissions.show', $admission) }}">#{{ $admission->id }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:700px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admissions.update', $admission) }}">
            @csrf @method('PUT')
            @include('admissions._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update
                </button>
                <a href="{{ route('admissions.show', $admission) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
