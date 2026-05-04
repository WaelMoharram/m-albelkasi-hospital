@extends('layouts.app')

@section('title', __('Patient List'))
@section('page_title', __('Patient List'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('Reports') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Patient List') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.patient-list') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('Month') }}</label>
                <input type="month" name="period"
                       value="{{ request('period', now()->format('Y-m')) }}"
                       class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('Insurance Company') }}</label>
                <select name="insurance_company_id" class="form-select" required>
                    <option value="">-- {{ __('Select') }} --</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}"
                            {{ request('insurance_company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary">
                    <i class="bi bi-search ms-1"></i> {{ __('Show') }}
                </button>
                @if ($data && $data['rows']->count())
                    <a href="{{ route('reports.patient-list.print', request()->query()) }}"
                       target="_blank"
                       class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-printer ms-1"></i> {{ __('Print PDF') }}
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if ($data)
    @if ($data['rows']->isEmpty())
        <div class="alert alert-info">{{ __('No discharged patients found for this period.') }}</div>
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ $data['insurance']?->name }}</strong>
                &mdash;
                {{ \Carbon\Carbon::createFromDate($data['year'], $data['month'], 1)->locale('ar')->isoFormat('MMMM YYYY') }}
            </div>
            <span class="badge bg-primary fs-6">{{ $data['rows']->count() }} {{ __('cases') }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle text-center mb-0" style="font-size:.82rem;">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Patient Name') }}</th>
                        <th>{{ __('Date of Birth') }}</th>
                        <th>{{ __('Age') }}</th>
                        <th>{{ __('Admission') }}</th>
                        <th>{{ __('Discharge') }}</th>
                        <th>{{ __('Days') }}</th>
                        <th>{{ __('Referral No.') }}</th>
                        <th>{{ __('Referral Source') }}</th>
                        <th>{{ __('Invoice Total') }}</th>
                        <th>{{ __('Per Day') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['seq'] }}</td>
                        <td class="text-start">{{ $row['patient']->name }}</td>
                        <td>{{ $row['patient']->dob?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $row['age'] ?? '—' }}</td>
                        <td>{{ $row['admission']->admission_date->format('Y-m-d') }}</td>
                        <td>{{ $row['admission']->discharge_date->format('Y-m-d') }}</td>
                        <td>{{ $row['days'] }}</td>
                        <td>{{ $row['referral_number'] ?? '—' }}</td>
                        <td>{{ $row['referral_source'] ?? '—' }}</td>
                        <td><strong>{{ number_format($row['invoice_total'], 2) }}</strong></td>
                        <td class="text-muted small">{{ number_format($row['per_day'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="6">{{ __('Total') }}</td>
                        <td>{{ $data['totals']['days'] }}</td>
                        <td colspan="2"></td>
                        <td>{{ number_format($data['totals']['invoice_total'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
@endif
@endsection
