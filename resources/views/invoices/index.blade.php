@extends('layouts.app')

@section('title', __('Invoices'))
@section('page_title', __('Invoices'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Invoices') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('invoices.index') }}" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="{{ __('Search patient name or national ID…') }}"
                   style="max-width:280px;">
            <select name="status" class="form-select form-select-sm" style="max-width:150px;">
                <option value="">{{ __('All statuses') }}</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>{{ __('Final') }}</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> {{ __('Clear') }}
                </a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('Patient') }}</th>
                    <th>{{ __('Insurance') }}</th>
                    <th>{{ __('Admission') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Total') }}</th>
                    <th class="text-start">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                @php $admission = $invoice->admission; $patient = $admission->patient; @endphp
                <tr>
                    <td class="text-muted small">{{ $invoice->id }}</td>
                    <td>
                        <div class="fw-medium">{{ $patient->name }}</div>
                        <div class="text-muted small font-monospace">{{ $patient->national_id }}</div>
                    </td>
                    <td class="small">{{ $patient->insuranceCompany->name ?? '—' }}</td>
                    <td class="small">
                        <a href="{{ route('admissions.show', $admission) }}" class="text-decoration-none">
                            #{{ $admission->id }}
                        </a>
                        — {{ $admission->admission_date->format('d/m/Y') }}
                    </td>
                    <td class="small">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    <td>
                        @if($invoice->status === 'draft')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('Draft') }}</span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Final') }}</span>
                        @endif
                    </td>
                    <td class="text-end fw-medium">{{ number_format($invoice->total_amount, 2) }}</td>
                    <td class="text-start">
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-sm btn-outline-dark"
                           target="_blank" title="{{ __('Print PDF') }}">
                            <i class="bi bi-printer"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">{{ __('No invoices found.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($invoices->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ __('Showing :from–:to of :total', ['from' => $invoices->firstItem(), 'to' => $invoices->lastItem(), 'total' => $invoices->total()]) }}</small>
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
