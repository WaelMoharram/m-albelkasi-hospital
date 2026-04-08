@extends('layouts.app')

@section('title', __('Dashboard'))
@section('page_title', __('Dashboard'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Dashboard') }}</li>
@endsection

@section('content')
<div class="row g-3">

    {{-- Welcome banner --}}
    <div class="col-12">
        <div class="alert alert-info py-2 mb-0">
            <i class="bi bi-info-circle ms-1"></i>
            {{ __('Welcome back,') }} <strong>{{ auth()->user()->name }}</strong>.
            {{ __('You are signed in as') }}
            @foreach(auth()->user()->getRoleNames() as $role)
                <span class="badge bg-primary">{{ ucwords(str_replace('_', ' ', $role)) }}</span>
            @endforeach
        </div>
    </div>

    {{-- Patients --}}
    @can('view_patients')
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('patients.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3 flex-shrink-0">
                        <i class="bi bi-people fs-3 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('Total Patients') }}</div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($stats['patients']) }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endcan

    {{-- Active Admissions --}}
    @can('view_admissions')
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admissions.index', ['status' => 'active']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3 flex-shrink-0">
                        <i class="bi bi-clipboard2-pulse fs-3 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('Active Admissions') }}</div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($stats['active_admissions']) }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endcan

    {{-- Draft Invoices --}}
    @can('view_invoices')
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('invoices.index', ['status' => 'draft']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-3 flex-shrink-0">
                        <i class="bi bi-receipt fs-3 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('Draft Invoices') }}</div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($stats['draft_invoices']) }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endcan

    {{-- Reports link --}}
    @can('view_reports')
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('reports.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-3 flex-shrink-0">
                        <i class="bi bi-file-earmark-bar-graph fs-3 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('Monthly Reports') }}</div>
                        <div class="fs-5 fw-semibold text-info mt-1">
                            {{ __('View') }} <i class="bi bi-arrow-left-short"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endcan

</div>
@endsection

@push('styles')
<style>
    .card-hover { transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.1) !important; }
</style>
@endpush
