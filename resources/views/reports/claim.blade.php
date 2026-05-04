@extends('layouts.app')

@section('title', __('Claim Sheet'))
@section('page_title', __('Claim Sheet'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('Reports') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Claim Sheet') }}</li>
@endsection

@section('content')
{{-- Filter form --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.claim') }}" class="row g-3 align-items-end">
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
                    <a href="{{ route('reports.claim.print', request()->query()) }}"
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
                        <th rowspan="2">#</th>
                        <th rowspan="2">{{ __('Patient Name') }}</th>
                        <th rowspan="2">{{ __('Card') }}</th>
                        <th rowspan="2">{{ __('Admission') }}</th>
                        <th rowspan="2">{{ __('Discharge') }}</th>
                        <th rowspan="2">{{ __('Days') }}</th>
                        @foreach ($data['categories'] as $cat)
                            <th>{{ $cat->name }}</th>
                        @endforeach
                        <th rowspan="2">{{ __('Stay Total') }}</th>
                        <th rowspan="2">{{ __('Labs') }}</th>
                        <th rowspan="2">{{ __('Local Meds') }}<br><small>-{{ number_format($data['local_discount'], 0) }}%</small></th>
                        <th rowspan="2">{{ __('Imported Meds') }}<br><small>-{{ number_format($data['imported_discount'], 0) }}%</small></th>
                        <th rowspan="2">{{ __('Supplies') }}</th>
                        <th rowspan="2">{{ __('Grand Total') }}</th>
                        <th rowspan="2">{{ __('Per Day') }}</th>
                    </tr>
                    <tr></tr>
                </thead>
                <tbody>
                    @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['seq'] }}</td>
                        <td class="text-start">{{ $row['patient']->name }}</td>
                        <td>{{ $row['admission']->referral_number ?? '—' }}</td>
                        <td>{{ $row['admission']->admission_date->format('Y-m-d') }}</td>
                        <td>{{ $row['admission']->discharge_date->format('Y-m-d') }}</td>
                        <td>{{ $row['days'] }}</td>
                        @foreach ($data['categories'] as $cat)
                            <td>{{ $row['by_category'][$cat->id] > 0 ? number_format($row['by_category'][$cat->id], 2) : '—' }}</td>
                        @endforeach
                        <td><strong>{{ number_format($row['stay_subtotal'], 2) }}</strong></td>
                        <td>{{ $row['labs'] > 0 ? number_format($row['labs'], 2) : '—' }}</td>
                        <td>{{ $row['local_meds'] > 0 ? number_format($row['local_meds'], 2) : '—' }}</td>
                        <td>{{ $row['imported_meds'] > 0 ? number_format($row['imported_meds'], 2) : '—' }}</td>
                        <td>—</td>
                        <td><strong>{{ number_format($row['grand_total'], 2) }}</strong></td>
                        <td class="text-muted small">{{ number_format($row['per_day'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="5">{{ __('Total') }}</td>
                        <td>{{ $data['totals']['days'] }}</td>
                        @foreach ($data['categories'] as $cat)
                            <td>{{ number_format($data['totals']['by_category'][$cat->id] ?? 0, 2) }}</td>
                        @endforeach
                        <td>{{ number_format($data['totals']['stay_subtotal'], 2) }}</td>
                        <td>{{ number_format($data['totals']['labs'], 2) }}</td>
                        <td>{{ number_format($data['totals']['local_meds'], 2) }}</td>
                        <td>{{ number_format($data['totals']['imported_meds'], 2) }}</td>
                        <td>—</td>
                        <td>{{ number_format($data['totals']['grand_total'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
@endif
@endsection
