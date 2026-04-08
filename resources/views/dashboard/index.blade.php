@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="alert alert-info py-2 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Welcome back, <strong>{{ auth()->user()->name }}</strong>.
            You are signed in as
            @foreach(auth()->user()->getRoleNames() as $role)
                <span class="badge bg-primary">{{ str_replace('_', ' ', $role) }}</span>
            @endforeach
        </div>
    </div>

    {{-- Stat cards placeholder --}}
    @can('view_patients')
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-people fs-3 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Patients</div>
                    <div class="fs-4 fw-bold">—</div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('view_admissions')
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-clipboard2-pulse fs-3 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Admissions</div>
                    <div class="fs-4 fw-bold">—</div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('view_invoices')
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-receipt fs-3 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Draft Invoices</div>
                    <div class="fs-4 fw-bold">—</div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @can('view_reports')
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-file-earmark-bar-graph fs-3 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Reports</div>
                    <div class="fs-4 fw-bold">—</div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection
