@extends('layouts.app')

@section('title', 'Edit Insurance Company')
@section('page_title', 'Edit Insurance Company')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('catalog.insurance.index') }}">Insurance Companies</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('catalog.insurance.update', $insuranceCompany) }}">
                    @csrf
                    @method('PUT')
                    @include('catalog.insurance._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Update
                        </button>
                        <a href="{{ route('catalog.insurance.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
