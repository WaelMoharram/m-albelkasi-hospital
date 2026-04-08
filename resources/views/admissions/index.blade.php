@extends('layouts.app')

@section('title', __('Admissions'))
@section('page_title', __('Admissions'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Admissions') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('admissions.index') }}" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="{{ __('Search patient name or national ID…') }}"
                   style="max-width:280px;">

            <select name="status" class="form-select form-select-sm" style="max-width:160px;">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active"     {{ request('status') === 'active'     ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="discharged" {{ request('status') === 'discharged' ? 'selected' : '' }}>{{ __('Discharged') }}</option>
            </select>

            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('admissions.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> {{ __('Clear') }}
                </a>
            @endif
        </form>

        @can('manage_admissions')
        <a href="{{ route('admissions.create') }}" class="btn btn-sm btn-primary me-auto">
            <i class="bi bi-plus-lg ms-1"></i> {{ __('New Admission') }}
        </a>
        @endcan
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Patient') }}</th>
                    <th>{{ __('Insurance') }}</th>
                    <th>{{ __('Admitted') }}</th>
                    <th>{{ __('Room / Ward') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Invoice Total') }}</th>
                    <th class="text-start">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admissions as $admission)
                <tr>
                    <td class="text-muted small">{{ $admission->id }}</td>
                    <td>
                        <div class="fw-medium">{{ $admission->patient->name }}</div>
                        <div class="text-muted small font-monospace">{{ $admission->patient->national_id }}</div>
                    </td>
                    <td class="small">{{ $admission->patient->insuranceCompany->name ?? '—' }}</td>
                    <td class="small">{{ $admission->admission_date->format('d/m/Y') }}</td>
                    <td class="small">
                        {{ $admission->room ?? '—' }}
                        @if($admission->ward)
                            <span class="text-muted">/ {{ $admission->ward }}</span>
                        @endif
                    </td>
                    <td>
                        @if($admission->isActive())
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                {{ __('Discharged') }} {{ $admission->discharge_date->format('d/m/Y') }}
                            </span>
                        @endif
                    </td>
                    <td class="small">
                        @if($admission->invoice)
                            <span class="{{ $admission->invoice->status === 'draft' ? 'text-muted' : 'fw-medium' }}">
                                {{ number_format($admission->invoice->total_amount, 2) }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-start">
                        <a href="{{ route('admissions.show', $admission) }}"
                           class="btn btn-sm btn-outline-secondary" title="{{ __('View') }}">
                            <i class="bi bi-eye"></i>
                        </a>
                        @can('manage_admissions')
                        @if($admission->isActive())
                        <a href="{{ route('admissions.edit', $admission) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">{{ __('No admissions found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($admissions->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ __('Showing :from–:to of :total', ['from' => $admissions->firstItem(), 'to' => $admissions->lastItem(), 'total' => $admissions->total()]) }}</small>
        {{ $admissions->links() }}
    </div>
    @endif
</div>
@endsection
