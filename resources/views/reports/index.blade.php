@extends('layouts.app')

@section('title', __('Monthly Report'))
@section('page_title', __('Monthly Report'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Reports') }}</li>
@endsection

@php
    use Carbon\Carbon;
    $monthLabel  = Carbon::createFromDate($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
    $periodValue = sprintf('%04d-%02d', $year, $month);
@endphp

@section('content')

{{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('reports.index') }}"
              class="d-flex flex-wrap align-items-end gap-3">

            <div>
                <label class="form-label mb-1 small fw-semibold" for="period">{{ __('Month') }}</label>
                <input id="period" type="month" name="period"
                       value="{{ $periodValue }}"
                       max="{{ now()->format('Y-m') }}"
                       class="form-control form-control-sm"
                       style="max-width:180px;">
            </div>

            <div class="d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel ms-1"></i> {{ __('Generate') }}
                </button>

                @if($rows->isNotEmpty())
                <a href="{{ route('reports.export', ['period' => $periodValue]) }}"
                   class="btn btn-sm btn-outline-dark" target="_blank">
                    <i class="bi bi-file-earmark-pdf ms-1"></i> {{ __('Export A3 PDF') }}
                </a>
                @endif
            </div>

            <div class="me-auto d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2">
                    <i class="bi bi-calendar3 ms-1"></i>{{ $monthLabel }}
                </span>
                @if($rows->isNotEmpty())
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                    {{ $rows->count() }} إدخال
                </span>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- ── Summary table ───────────────────────────────────────────────────── --}}
@if($rows->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-25"></i>
            لا توجد إدخالات لشهر <strong>{{ $monthLabel }}</strong>.
        </div>
    </div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="min-width:1100px;">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width:36px;">#</th>
                    <th>{{ __('Patient') }}</th>
                    <th>{{ __('Insurance') }}</th>
                    <th class="text-center">{{ __('Admitted') }}</th>
                    <th class="text-center">{{ __('Discharged') }}</th>
                    <th class="text-end">
                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Local Meds') }}</span>
                    </th>
                    <th class="text-end">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('Imported Meds') }}</span>
                    </th>
                    <th class="text-end">
                        <span class="badge bg-info-subtle text-info border border-info-subtle">{{ __('Lab') }}</span>
                    </th>
                    <th class="text-end">
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ __('Radiology') }}</span>
                    </th>
                    <th class="text-end">
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">يومي</span>
                    </th>
                    <th class="text-end fw-bold">{{ __('Grand Total') }}</th>
                    <th class="text-center" style="width:48px;"></th>
                </tr>
            </thead>

            <tbody>
                @foreach ($rows as $i => $row)
                @php $admission = $row['admission']; @endphp
                <tr>
                    <td class="text-center text-muted small">{{ $i + 1 }}</td>

                    <td>
                        <div class="fw-medium">{{ $row['patient']->name }}</div>
                        <div class="text-muted small font-monospace">{{ $row['patient']->national_id }}</div>
                    </td>

                    <td class="small">{{ $row['insurance']->name ?? '—' }}</td>

                    <td class="text-center small">{{ $admission->admission_date->format('d/m/Y') }}</td>

                    <td class="text-center small">
                        @if($admission->discharge_date)
                            {{ $admission->discharge_date->format('d/m/Y') }}
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                        @endif
                    </td>

                    <td class="text-end small">
                        {{ $row['local_med'] > 0 ? number_format($row['local_med'], 2) : '—' }}
                    </td>
                    <td class="text-end small">
                        {{ $row['imported_med'] > 0 ? number_format($row['imported_med'], 2) : '—' }}
                    </td>
                    <td class="text-end small">
                        {{ $row['lab'] > 0 ? number_format($row['lab'], 2) : '—' }}
                    </td>
                    <td class="text-end small">
                        {{ $row['radiology'] > 0 ? number_format($row['radiology'], 2) : '—' }}
                    </td>
                    <td class="text-end small">
                        {{ $row['daily'] > 0 ? number_format($row['daily'], 2) : '—' }}
                    </td>

                    <td class="text-end fw-bold">{{ number_format($row['grand_total'], 2) }}</td>

                    <td class="text-center">
                        @if($admission->invoice)
                        <a href="{{ route('invoices.show', $admission->invoice) }}"
                           class="btn btn-xs btn-outline-secondary border-0 p-0 px-1"
                           title="{{ __('View Invoice') }}">
                            <i class="bi bi-receipt small"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>

            {{-- Totals footer --}}
            <tfoot class="table-dark">
                <tr>
                    <td colspan="5" class="fw-semibold small text-end">{{ __('Column Totals') }}</td>
                    <td class="text-end fw-bold small">{{ number_format($totals['local_med'], 2) }}</td>
                    <td class="text-end fw-bold small">{{ number_format($totals['imported_med'], 2) }}</td>
                    <td class="text-end fw-bold small">{{ number_format($totals['lab'], 2) }}</td>
                    <td class="text-end fw-bold small">{{ number_format($totals['radiology'], 2) }}</td>
                    <td class="text-end fw-bold small">{{ number_format($totals['daily'], 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($totals['grand_total'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endsection
