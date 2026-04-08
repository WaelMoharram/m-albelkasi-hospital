@extends('layouts.app')

@section('title', 'Add Service')
@section('page_title', 'Add Service')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.services.index') }}">Services</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('catalog.services.store') }}">
                    @csrf
                    @include('catalog.services._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Save
                        </button>
                        <a href="{{ route('catalog.services.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
