@extends('layouts.app')

@section('title', __('Performance Indicators'))
@section('page_title', __('Performance Indicators'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('Reports') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Performance Indicators') }}</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.performance') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('Month') }}</label>
                <input type="month" name="period"
                       value="{{ request('period', now()->format('Y-m')) }}"
                       class="form-control">
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary">
                    <i class="bi bi-search ms-1"></i> {{ __('Show') }}
                </button>
                @if ($data)
                    <a href="{{ route('reports.performance.print', request()->query()) }}"
                       target="_blank" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-printer ms-1"></i> {{ __('Print PDF') }}
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if ($data)
<div class="row g-4">
    {{-- Basic Data --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-database ms-2 text-primary"></i> {{ __('Basic Data') }}
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                        <tr><th class="text-end pe-3" style="width:60%">عدد أيام الشهر</th><td class="text-center fw-bold">{{ $data['days_in_month'] }}</td></tr>
                        <tr><th class="text-end pe-3">عدد أسرة الرعاية المركزة</th><td class="text-center fw-bold">{{ $data['icu_beds'] }}</td></tr>
                        <tr><th class="text-end pe-3">إجمالي أيام الإقامة المتاحة</th><td class="text-center fw-bold">{{ $data['available_days'] }}</td></tr>
                        <tr><th class="text-end pe-3">عدد المرضى خلال الشهر</th><td class="text-center fw-bold text-primary">{{ $data['patient_count'] }}</td></tr>
                        <tr><th class="text-end pe-3">عدد أيام إقامة المرضى</th><td class="text-center fw-bold">{{ $data['stay_days'] }}</td></tr>
                        <tr><th class="text-end pe-3">عدد الأيام المتاحة المتبقية</th><td class="text-center fw-bold">{{ $data['remaining_days'] }}</td></tr>
                        <tr><th class="text-end pe-3">عدد الوفيات خلال 24 ساعة</th><td class="text-center fw-bold text-danger">{{ $data['deaths_24h'] }}</td></tr>
                        <tr><th class="text-end pe-3">عدد الوفيات بالرعاية المركزة</th><td class="text-center fw-bold text-danger">{{ $data['deaths'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Indicators --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-graph-up ms-2 text-success"></i> {{ __('Performance Indicators') }}
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th class="text-end pe-3" style="width:70%">متوسط التردد اليومي على الرعاية المركزة</th>
                            <td class="text-center fw-bold">{{ number_format($data['avg_daily_freq'], 6) }}</td>
                        </tr>
                        <tr>
                            <th class="text-end pe-3">معدل وفيات الرعاية المركزة</th>
                            <td class="text-center fw-bold">{{ number_format($data['mortality_rate'], 6) }}</td>
                        </tr>
                        <tr>
                            <th class="text-end pe-3">متوسط فترة الإقامة بالرعاية المركزة</th>
                            <td class="text-center fw-bold">{{ number_format($data['avg_stay'], 6) }}</td>
                        </tr>
                        <tr>
                            <th class="text-end pe-3">معدل دوران السرير بالرعاية المركزة</th>
                            <td class="text-center fw-bold">{{ number_format($data['bed_turnover'], 6) }}</td>
                        </tr>
                        <tr>
                            <th class="text-end pe-3">معدل إشغال أسرة الرعاية المركزة</th>
                            <td class="text-center fw-bold text-success">{{ number_format($data['occupancy_rate'] * 100, 2) }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
