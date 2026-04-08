@extends('layouts.app')

@section('title', 'New Admission')
@section('page_title', 'New Admission')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">Admissions</a></li>
    <li class="breadcrumb-item active">New</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width:700px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admissions.store') }}">
            @csrf
            @include('admissions._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-clipboard2-plus me-1"></i> Admit Patient
                </button>
                <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
