@extends('layouts.app')

@section('title', __('Summary'))
@section('page_title', __('Summary'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('Reports') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Summary') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.summary') }}" class="row g-3 align-items-end">
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
                    <a href="{{ route('reports.summary.print', request()->query()) }}"
                       target="_blank" class="btn btn-outline-secondary ms-2">
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
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('No.') }}</th>
                        <th>{{ __('Service Type') }}</th>
                        <th>{{ __('Law / Card') }}</th>
                        <th>{{ __('Cases') }}</th>
                        <th>{{ __('Stay Days') }}</th>
                        <th>{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['rows'] as $row)
                    <tr>
                        <td>{{ $row['seq'] }}</td>
                        <td>{{ $row['service_type'] }}</td>
                        <td>{{ $row['law'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td>{{ $row['days'] }}</td>
                        <td><strong>{{ number_format($row['amount'], 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="3">{{ __('Total') }}</td>
                        <td>{{ $data['totals']['count'] }}</td>
                        <td>{{ $data['totals']['days'] }}</td>
                        <td>{{ number_format($data['totals']['amount'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
@endif
@endsection
