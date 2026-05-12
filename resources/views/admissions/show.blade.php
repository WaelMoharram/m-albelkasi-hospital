@extends('layouts.app')

@section('title', 'إدخال #' . $admission->id)
@section('page_title', 'إدخال #' . $admission->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">{{ __('Admissions') }}</a></li>
    <li class="breadcrumb-item active">#{{ $admission->id }}</li>
@endsection

@section('content')

{{-- ── Header card ─────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-4">
        <div class="row align-items-start">
            <div class="col-md-8">
                <h5 class="fw-bold mb-1">{{ $admission->patient->name }}</h5>
                <div class="text-muted small mb-2">
                    {{ __('National ID') }}: <span class="font-monospace">{{ $admission->patient->national_id }}</span>
                    &nbsp;·&nbsp;
                    {{ __('DOB') }}: {{ $admission->patient->dob->format('d/m/Y') }}
                    &nbsp;·&nbsp;
                    {{ $admission->patient->gender === 'male' ? __('Male') : __('Female') }}
                </div>
                <div class="d-flex flex-wrap gap-3 small">
                    <span><i class="bi bi-shield-check text-primary ms-1"></i>
                        {{ $admission->patient->insuranceCompany->name ?? '—' }}
                    </span>
                    @if($admission->referral_number)
                    <span><i class="bi bi-card-text text-secondary ms-1"></i>
                        {{ __('Referral #') }}: {{ $admission->referral_number }}
                    </span>
                    @endif
                    <span><i class="bi bi-geo-alt text-secondary ms-1"></i>
                        {{ __('Room') }} {{ $admission->room ?? '—' }} / {{ $admission->ward ?? '—' }}
                    </span>
                </div>
            </div>

            <div class="col-md-4 text-md-start mt-3 mt-md-0">
                @if($admission->isActive())
                    <span class="badge fs-6 bg-success mb-2">{{ __('Active') }}</span><br>
                    <div class="small text-muted">{{ __('Admitted') }}: {{ $admission->admission_date->format('d/m/Y') }}</div>
                    @can('manage_admissions')
                    <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#dischargeModal">
                        <i class="bi bi-box-arrow-left ms-1"></i> {{ __('Discharge') }}
                    </button>
                    <a href="{{ route('admissions.edit', $admission) }}" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-pencil ms-1"></i> {{ __('Edit') }}
                    </a>
                    @endcan
                @else
                    <span class="badge fs-6 bg-secondary mb-2">{{ __('Discharged') }}</span><br>
                    <div class="small text-muted">{{ $admission->admission_date->format('d/m/Y') }} ← {{ $admission->discharge_date->format('d/m/Y') }}</div>
                    <div class="small text-muted">{{ $admission->admission_date->diffInDays($admission->discharge_date) + 1 }} يوم</div>
                @endif
                @if($admission->invoice)
                <a href="{{ route('invoices.show', $admission->invoice) }}"
                   class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="bi bi-receipt ms-1"></i> {{ __('View Invoice') }}
                </a>
                @endif
                @can('delete_admissions')
                <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                        data-bs-toggle="modal" data-bs-target="#deleteAdmissionModal">
                    <i class="bi bi-trash ms-1"></i> {{ __('Delete') }}
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

{{-- ── Invoice summary ─────────────────────────────────────────────────── --}}
@if($admission->invoice)
@php
    $invoice = $admission->invoice;
    $sections = [
        'daily'        => ['label' => __('Daily Services'),      'icon' => 'bi-calendar-check',  'color' => 'primary'],
        'lab'          => ['label' => __('Lab Tests'),            'icon' => 'bi-eyedropper',       'color' => 'info'],
        'radiology'    => ['label' => __('Radiology'),            'icon' => 'bi-radioactive',      'color' => 'warning'],
        'local_med'    => ['label' => __('Local Medications'),    'icon' => 'bi-capsule',          'color' => 'success'],
        'imported_med' => ['label' => __('Imported Medications'), 'icon' => 'bi-capsule-pill',     'color' => 'danger'],
    ];
    $groupedItems = $invoice->items->groupBy('section');
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <div>
            <span class="fw-semibold">{{ __('Invoices') }} #{{ $invoice->id }}</span>
            <span class="ms-2 badge {{ $invoice->status === 'draft' ? 'bg-secondary' : 'bg-success' }}">
                {{ $invoice->status === 'draft' ? __('Draft') : __('Final') }}
            </span>
        </div>
        <div class="fw-bold">{{ __('Total') }}: {{ number_format($invoice->total_amount, 2) }}</div>
    </div>

    @foreach ($sections as $key => $meta)
        @if(isset($groupedItems[$key]) && $groupedItems[$key]->isNotEmpty())
        <div class="card-body border-top py-3">
            <h6 class="text-{{ $meta['color'] }} mb-2">
                <i class="bi {{ $meta['icon'] }} ms-1"></i>{{ $meta['label'] }}
            </h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Item') }}</th>
                            @if($key === 'daily') <th>{{ __('Date') }}</th> @endif
                            <th class="text-end">{{ __('Qty') }}</th>
                            <th class="text-end">{{ __('Unit Price') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupedItems[$key] as $item)
                        <tr>
                            <td>{{ $item->itemable->name ?? '—' }}</td>
                            @if($key === 'daily')
                            <td class="small text-muted">{{ $item->service_date?->format('d/m/Y') }}</td>
                            @endif
                            <td class="text-end">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">{{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="{{ $key === 'daily' ? 4 : 3 }}" class="text-end fw-semibold small">{{ __('Subtotal') }}</td>
                            <td class="text-end fw-semibold">
                                {{ number_format($groupedItems[$key]->sum('total'), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    @endforeach
</div>
@else
<div class="alert alert-warning">{{ __('No invoice found for this admission.') }}</div>
@endif

{{-- ── Discharge Modal ─────────────────────────────────────────────────── --}}
@if($admission->isActive())
@can('manage_admissions')
<div class="modal fade" id="dischargeModal" tabindex="-1" aria-labelledby="dischargeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admissions.discharge', $admission) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="dischargeModalLabel">
                        <i class="bi bi-box-arrow-left ms-1 text-warning"></i> {{ __('Discharge Patient') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        {{ __('This will finalise daily services up to the discharge date and close the invoice.') }}
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="discharge_date">
                            {{ __('Discharge Date') }} <span class="text-danger">*</span>
                        </label>
                        <input id="discharge_date" type="date" name="discharge_date"
                               value="{{ now()->toDateString() }}"
                               min="{{ $admission->admission_date->toDateString() }}"
                               max="{{ now()->toDateString() }}"
                               class="form-control @error('discharge_date') is-invalid @enderror"
                               required>
                        @error('discharge_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="discharge_reason">{{ __('Discharge Reason') }} <span class="text-danger">*</span></label>
                        <select id="discharge_reason" name="discharge_reason" class="form-select" required>
                            <option value="discharged">{{ __('Discharged (recovered)') }}</option>
                            <option value="died">{{ __('Died') }}</option>
                            <option value="transferred">{{ __('Transferred') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-box-arrow-left ms-1"></i> {{ __('Confirm Discharge') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endif

@can('delete_admissions')
<div class="modal fade" id="deleteAdmissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admissions.destroy', $admission) }}">
                @csrf @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle ms-1"></i> {{ __('Delete Admission') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Are you sure you want to delete the admission for') }}
                        <strong>{{ $admission->patient->name }}</strong>؟
                    </p>
                    <p class="text-danger small mb-0">
                        <i class="bi bi-exclamation-circle ms-1"></i>
                        {{ __('This will permanently delete the admission and its invoice.') }}
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash ms-1"></i> {{ __('Delete') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection
