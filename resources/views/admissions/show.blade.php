@extends('layouts.app')

@section('title', 'Admission #' . $admission->id)
@section('page_title', 'Admission #' . $admission->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">Admissions</a></li>
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
                    National ID: <span class="font-monospace">{{ $admission->patient->national_id }}</span>
                    &nbsp;·&nbsp;
                    DOB: {{ $admission->patient->dob->format('d/m/Y') }}
                    &nbsp;·&nbsp;
                    {{ ucfirst($admission->patient->gender) }}
                </div>
                <div class="d-flex flex-wrap gap-3 small">
                    <span><i class="bi bi-shield-check text-primary me-1"></i>
                        {{ $admission->patient->insuranceCompany->name ?? '—' }}
                    </span>
                    <span><i class="bi bi-card-text text-secondary me-1"></i>
                        Policy: {{ $admission->patient->policy_number }}
                    </span>
                    <span><i class="bi bi-geo-alt text-secondary me-1"></i>
                        Room {{ $admission->room ?? '—' }} / {{ $admission->ward ?? '—' }}
                    </span>
                </div>
            </div>

            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                @if($admission->isActive())
                    <span class="badge fs-6 bg-success mb-2">Active</span><br>
                    <div class="small text-muted">Admitted: {{ $admission->admission_date->format('d/m/Y') }}</div>
                    @can('manage_admissions')
                    <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#dischargeModal">
                        <i class="bi bi-box-arrow-right me-1"></i> Discharge
                    </button>
                    <a href="{{ route('admissions.edit', $admission) }}" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    @endcan
                @else
                    <span class="badge fs-6 bg-secondary mb-2">Discharged</span><br>
                    <div class="small text-muted">{{ $admission->admission_date->format('d/m/Y') }} → {{ $admission->discharge_date->format('d/m/Y') }}</div>
                    <div class="small text-muted">{{ $admission->admission_date->diffInDays($admission->discharge_date) + 1 }} day(s)</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Invoice summary ─────────────────────────────────────────────────── --}}
@if($admission->invoice)
@php
    $invoice = $admission->invoice;
    $sections = [
        'daily'        => ['label' => 'Daily Services',       'icon' => 'bi-calendar-check',     'color' => 'primary'],
        'lab'          => ['label' => 'Lab Tests',             'icon' => 'bi-eyedropper',          'color' => 'info'],
        'radiology'    => ['label' => 'Radiology',             'icon' => 'bi-radioactive',         'color' => 'warning'],
        'local_med'    => ['label' => 'Local Medications',     'icon' => 'bi-capsule',             'color' => 'success'],
        'imported_med' => ['label' => 'Imported Medications',  'icon' => 'bi-capsule-pill',        'color' => 'danger'],
    ];
    $groupedItems = $invoice->items->groupBy('section');
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <div>
            <span class="fw-semibold">Invoice #{{ $invoice->id }}</span>
            <span class="ms-2 badge {{ $invoice->status === 'draft' ? 'bg-secondary' : 'bg-success' }}">
                {{ ucfirst($invoice->status) }}
            </span>
        </div>
        <div class="fw-bold">Total: {{ number_format($invoice->total_amount, 2) }}</div>
    </div>

    @foreach ($sections as $key => $meta)
        @if(isset($groupedItems[$key]) && $groupedItems[$key]->isNotEmpty())
        <div class="card-body border-top py-3">
            <h6 class="text-{{ $meta['color'] }} mb-2">
                <i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
            </h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            @if($key === 'daily') <th>Date</th> @endif
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
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
                            <td colspan="{{ $key === 'daily' ? 4 : 3 }}" class="text-end fw-semibold small">Subtotal</td>
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
<div class="alert alert-warning">No invoice found for this admission.</div>
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
                        <i class="bi bi-box-arrow-right me-1 text-warning"></i> Discharge Patient
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        This will finalise daily services up to the discharge date and close the invoice.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="discharge_date">
                            Discharge Date <span class="text-danger">*</span>
                        </label>
                        <input id="discharge_date" type="date" name="discharge_date"
                               value="{{ now()->toDateString() }}"
                               min="{{ $admission->admission_date->toDateString() }}"
                               max="{{ now()->toDateString() }}"
                               class="form-control @error('discharge_date') is-invalid @enderror"
                               required>
                        @error('discharge_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-box-arrow-right me-1"></i> Confirm Discharge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endif

@endsection
