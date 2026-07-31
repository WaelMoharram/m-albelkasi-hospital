@extends('layouts.app')

@section('title', __('Invoices') . ' #' . $invoice->id)
@section('page_title', __('Invoices') . ' #' . $invoice->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">{{ __('Invoices') }}</a></li>
    <li class="breadcrumb-item active">#{{ $invoice->id }}</li>
@endsection

@php
    $admission = $invoice->admission;
    $patient   = $admission->patient;
    $isDraft   = $invoice->status === 'draft';

    $grouped = $invoice->items->groupBy('section');

    // Group daily items by invoice_category for the الفاتورة tab.
    // Supplies (section='supplies') always go to their own tab — never here.
    // Items without invoice_category_id are also excluded (no "أخرى" fallback).
    $dailyFlat   = $grouped['daily'] ?? collect();
    $allSvcItems = $dailyFlat->filter(function ($_item) {
        $_svc = $_item->itemable;
        return $_svc
            && ($_svc instanceof \App\Models\Service)
            && $_svc->category !== 'supplies'
            && $_svc->invoice_category_id !== null;
    });
    $dailyCategoryGroups = collect();
    foreach ($allSvcItems as $_item) {
        $_svc = $_item->itemable;
        $_cat = $_svc->invoiceCategory ?? null;
        if (!$_cat) continue;
        $_key = 'c' . $_cat->id;
        if (!$dailyCategoryGroups->has($_key)) {
            $dailyCategoryGroups->put($_key, [
                'id'         => $_cat->id,
                'name'       => $_cat->name,
                'sort_order' => $_cat->sort_order,
                'items'      => collect(),
            ]);
        }
        $dailyCategoryGroups[$_key]['items']->push($_item);
    }
    $dailyCategoryGroups = $dailyCategoryGroups->sortBy('sort_order');

    // Aggregate repeated items (e.g. daily charges per day) by service within each category
    $dailyCategoryGroups->transform(function ($group) {
        $group['items'] = $group['items']
            ->groupBy('itemable_id')
            ->map(function ($rows) {
                $first      = $rows->first();
                $agg        = new \stdClass;
                $agg->id         = $first->id;
                $agg->itemable   = $first->itemable;
                $agg->qty        = $rows->sum('qty');
                $agg->perDayQty  = (int) $first->qty;   // qty per single record (per day)
                $agg->unit_price = (float) $first->unit_price;
                $agg->total      = (float) $rows->sum('total');
                $agg->section    = $first->section;
                $agg->isSingle   = $rows->count() === 1;
                $agg->singleItem = $rows->count() === 1 ? $first : null;
                return $agg;
            })
            ->values();
        return $group;
    });

    // Row count actually rendered in the الفاتورة table — one row per
    // aggregated item per category, not one per raw (per-day) invoice_item.
    $dailyTabRowCount = $dailyCategoryGroups->sum(fn ($group) => $group['items']->count());

    $sections = [
        'local_med'    => ['label' => __('Local Medications'),    'subtotal_label' => __('Local Medications Subtotal'),    'icon' => 'bi-capsule',      'color' => 'success'],
        'imported_med' => ['label' => __('Imported Medications'), 'subtotal_label' => __('Imported Medications Subtotal'), 'icon' => 'bi-capsule-pill', 'color' => 'warning'],
        'supplies'     => ['label' => __('Supplies'),             'subtotal_label' => __('Supplies Subtotal'),             'icon' => 'bi-box-seam',     'color' => 'secondary'],
        'lab'          => ['label' => __('Lab'),                  'subtotal_label' => __('Lab Subtotal'),                  'icon' => 'bi-eyedropper',   'color' => 'info'],
    ];

    $billableTotal = $invoice->items
        ->whereIn('section', array_keys($sections))
        ->sum('total');

    // ── HIO export payload — read by the browser extension that fills the ──
    // government insurance portal. Bucket names map to that portal's two
    // entry sections: "procedures" → اضافة اجراءات (invoice + lab items),
    // "medications"/"supplies" → اضافة الأدوية والمستلزمات الطبية.
    $hioBucket = function ($rawItems) {
        return $rawItems->groupBy('itemable_id')->map(function ($rows) {
            $first = $rows->first();
            return [
                'code'       => $first->itemable->code ?? null,
                'name'       => $first->itemable->name ?? null,
                'qty'        => (int) $rows->sum('qty'),
                'unit_price' => (float) $first->unit_price,
                'unit'       => $first->itemable->unit ?? null,
            ];
        })->values();
    };

    // Built from $dailyFlat directly (not $dailyCategoryGroups / $allSvcItems)
    // because those are filtered to invoice_category_id !== null — that
    // filter only exists for the الفاتورة tab's display grouping. Most daily
    // services have no invoice_category_id assigned but still carry a real
    // HIO code, so reusing that filter here silently dropped them from the
    // export instead of sending them to HIO at all.
    $hioDailyItems = $dailyFlat->filter(function ($_item) {
        $_svc = $_item->itemable;
        return $_svc && ($_svc instanceof \App\Models\Service) && $_svc->category !== 'supplies';
    });

    $hioProcedures = $hioBucket($hioDailyItems)
        ->merge($hioBucket($grouped['lab'] ?? collect()))
        ->merge($hioBucket($grouped['other'] ?? collect()))
        ->values();

    $hioExport = [
        'invoice_id'          => $invoice->id,
        'admission_id'        => $admission->id,
        'patient_name'        => $patient->name,
        'national_id'         => $patient->national_id,
        'exported_at'         => now()->toIso8601String(),
        'procedures'          => $hioProcedures,
        'local_medications'   => $hioBucket($grouped['local_med'] ?? collect())->values(),
        'imported_medications' => $hioBucket($grouped['imported_med'] ?? collect())->values(),
        'supplies'            => $hioBucket($grouped['supplies'] ?? collect())->values(),
    ];
@endphp

@section('content')

{{-- Read by the HIO autofill browser extension — see /hio-extension --}}
<script type="application/json" id="hio-export-data">{!! json_encode($hioExport, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

{{-- ── Action bar ─────────────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div>
        @if($isDraft)
            <span class="badge fs-6 bg-warning text-dark">
                <i class="bi bi-pencil-square ms-1"></i>{{ __('Draft') }}
            </span>
        @else
            <span class="badge fs-6 bg-success">
                <i class="bi bi-lock-fill ms-1"></i>{{ __('Final') }}
            </span>
        @endif
    </div>

    <div class="me-auto d-flex gap-2">
        @if($isDraft)
            @can('edit_invoices')
            <form method="POST" action="{{ route('invoices.finalize', $invoice) }}"
                  onsubmit="return confirm('{{ __('Finalise invoice') }} #{{ $invoice->id }}؟ {{ __('This cannot be undone.') }}')">
                @csrf
                <button class="btn btn-sm btn-success">
                    <i class="bi bi-lock ms-1"></i> {{ __('Finalise') }}
                </button>
            </form>
            @endcan
        @endif
        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-printer ms-1"></i> {{ __('Print PDF') }}
        </a>
        <a href="{{ route('admissions.show', $admission) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-right ms-1"></i> {{ __('Admission') }}
        </a>
        @can('delete_invoices')
        <button type="button" class="btn btn-sm btn-outline-danger"
                data-bs-toggle="modal" data-bs-target="#deleteInvoiceModal">
            <i class="bi bi-trash ms-1"></i> {{ __('Delete Invoice') }}
        </button>
        @endcan
    </div>
</div>

@can('delete_invoices')
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}">
                @csrf @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle ms-1"></i> {{ __('Delete Invoice') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Are you sure you want to delete the invoice for') }}
                        <strong>{{ $patient->name }}</strong>؟
                    </p>
                    <p class="text-danger small mb-0">
                        <i class="bi bi-exclamation-circle ms-1"></i>
                        {{ __('This will permanently delete the invoice and all its items.') }}
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

{{-- ── Patient / Admission header + indicators — one compact strip ─────── --}}
@php
    $patientTypeLabels = ['pension' => __('Pension'), 'student' => __('Student'), 'employee' => __('Employee')];
@endphp
<style>
    .field-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: #f1f4fa;
        border: 1px solid #dde3ee;
        border-radius: 999px;
        padding: 0.15rem 0.7rem;
        white-space: nowrap;
        max-width: 100%;
    }
    .field-chip .field-label,
    .field-chip .field-value { white-space: nowrap; }
    .field-chip .field-label { font-size: 0.7rem; color: #6c757d; font-weight: 600; }
    .field-chip .field-value { font-size: 0.8125rem; font-weight: 600; color: #212529; }
    @media (max-width: 575.98px) {
        .field-chip { padding: 0.15rem 0.55rem; }
        .field-chip .field-label { font-size: 0.65rem; }
        .field-chip .field-value { font-size: 0.75rem; }
    }
</style>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap column-gap-2 row-gap-2 align-items-center mb-2">
            <span class="field-chip">
                <span class="field-label">{{ __('Patient') }}:</span>
                <span class="field-value">{{ $patient->name }} <span class="text-muted font-monospace fw-normal">({{ $patient->national_id }})</span></span>
            </span>
            @if($admissionIndicators['age'])
            <span class="field-chip">
                <span class="field-label">{{ __('Age') }}:</span>
                <span class="field-value">{{ $admissionIndicators['age'] }}</span>
            </span>
            @endif
            @if($admission->referral_number)
            <span class="field-chip">
                <span class="field-label">{{ __('Referral #') }}:</span>
                <span class="field-value">{{ $admission->referral_number }}</span>
            </span>
            @endif
            <span class="field-chip">
                <span class="field-label">{{ __('Insurance') }}:</span>
                <span class="field-value">{{ $patient->insuranceCompany->name ?? '—' }}</span>
            </span>
            <span class="field-chip">
                <span class="field-label">{{ __('Room') }}:</span>
                <span class="field-value">{{ $admission->room ?? '—' }} / {{ $admission->ward ?? '—' }}</span>
            </span>
        </div>
        @if($admission->diagnosis)
        <div class="d-flex flex-wrap column-gap-2 row-gap-2 align-items-center mb-2">
            <span class="field-chip">
                <span class="field-label">{{ __('Diagnosis') }}:</span>
                <span class="field-value">{{ $admission->diagnosis }}</span>
            </span>
        </div>
        @endif
        <div class="d-flex flex-wrap column-gap-2 row-gap-2 align-items-center mb-2">
            <span class="field-chip">
                <span class="field-label">{{ __('Admission') }}:</span>
                <span class="field-value">
                    <a href="{{ route('admissions.show', $admission) }}" class="text-decoration-none">#{{ $admission->id }}</a>
                    — {{ $admission->admission_date->format('d/m/Y') }}
                </span>
            </span>
            <span class="field-chip">
                <span class="field-label">{{ __('Discharge') }}:</span>
                <span class="field-value">
                    @if($admission->discharge_date)
                        {{ $admission->discharge_date->format('d/m/Y') }}
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Active') }}</span>
                    @endif
                </span>
            </span>
            <span class="field-chip">
                <span class="field-label">{{ __('Duration') }}:</span>
                <span class="field-value">{{ $admissionIndicators['days'] }} {{ __('Days') }}</span>
            </span>
            <span class="field-chip">
                <span class="field-label">{{ __('Remaining Available Days This Month') }}:</span>
                <span class="field-value">{{ number_format($indicators['remaining_days']) }} <span class="text-muted fw-normal">/ {{ number_format($indicators['available_days']) }}</span></span>
            </span>
            <span class="field-chip">
                <span class="field-label">{{ __('Cost per Day') }}:</span>
                <span class="field-value">{{ number_format($admissionIndicators['cost_per_day'], 2) }}</span>
            </span>
        </div>
        @if($admission->patient_type)
        <div class="d-flex flex-wrap column-gap-2 row-gap-2 align-items-center">
            <span class="field-chip">
                <span class="field-label">{{ __('Patient Type') }}:</span>
                <span class="field-value">{{ $patientTypeLabels[$admission->patient_type] }}</span>
            </span>
        </div>
        @endif
    </div>
</div>

{{-- ── 4 Invoice Sections as Tabs ───────────────────────────────────────── --}}
<style>
#invoiceSectionTabs { --bs-nav-tabs-link-active-color: #212529; }
#invoiceSectionTabs .nav-link          { color: #6c757d !important; }
#invoiceSectionTabs .nav-link.active   { color: #212529 !important; font-weight: 600; }
#invoiceSectionTabs .nav-link:hover:not(.active) { color: #343a40 !important; }
</style>

<div class="card border-0 shadow-sm">

    {{-- Bulk import panel --}}
    @if($isDraft)
    @canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
    <div class="card-body border-bottom py-2 px-3 bg-light bg-opacity-50">
        <button class="btn btn-sm btn-outline-success" type="button"
                data-bs-toggle="collapse" data-bs-target="#bulkImportPanel">
            <i class="bi bi-table ms-1"></i> {{ __('Bulk import from Excel') }}
        </button>
        <div class="collapse mt-2" id="bulkImportPanel">
            <p class="text-muted small mb-2">
                {{ __('Upload an Excel sheet — columns in order: Item Name, Qty, Item Code. Matches medications first, then supplies — by code, then by name. If neither is found, the row is added to the warning list.') }}
                <a href="{{ route('invoices.items.bulk-example') }}" class="d-block mt-1">
                    <i class="bi bi-download ms-1"></i>{{ __('Download an example sheet') }}
                </a>
            </p>
            <input type="file" id="bulkFileInput" class="form-control form-control-sm mb-2"
                   accept=".xlsx,.xls,.csv">
            <div class="d-flex gap-2 align-items-center">
                <button id="bulkImportBtn" type="button" class="btn btn-sm btn-success">
                    <i class="bi bi-check2-all ms-1"></i> {{ __('Add to Invoice') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="document.getElementById('bulkFileInput').value='';document.getElementById('bulkResult').innerHTML=''">
                    {{ __('Clear') }}
                </button>
            </div>
            <div id="bulkResult" class="mt-2"></div>
        </div>
    </div>
    @endcanany
    @endif

    {{-- Tab nav --}}
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
        <ul class="nav nav-tabs card-header-tabs" id="invoiceSectionTabs" role="tablist">

            {{-- الفاتورة tab (first, active) — all services grouped by category --}}
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-daily"
                        type="button" role="tab">
                    {{ __('Invoice') }}
                    <span class="badge bg-secondary-subtle text-secondary ms-1 {{ $dailyTabRowCount === 0 ? 'd-none' : '' }}"
                          id="badge-daily">{{ $dailyTabRowCount }}</span>
                </button>
            </li>

            {{-- 4 section tabs --}}
            @foreach ($sections as $sectionKey => $meta)
            @php $tabItems = $grouped[$sectionKey] ?? collect(); @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-{{ $sectionKey }}-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-{{ $sectionKey }}"
                        type="button" role="tab">
                    {{ $meta['label'] }}
                    <span class="badge bg-secondary-subtle text-secondary ms-1 {{ $tabItems->isEmpty() ? 'd-none' : '' }}"
                          id="badge-{{ $sectionKey }}">{{ $tabItems->count() }}</span>
                </button>
            </li>
            @endforeach
        </ul>
    </div>

    {{-- Tab content --}}
    <div class="tab-content border-bottom">

        {{-- ── "الفاتورة" tab — daily services grouped by invoice category ── --}}
        <div class="tab-pane fade show active p-0" id="tab-daily" role="tabpanel">
            @if($isDraft)
            @canany(['add_invoice_items', 'edit_invoices'])
            <div class="d-flex justify-content-end p-2 border-bottom bg-light bg-opacity-50">
                <button type="button" class="btn btn-sm btn-outline-danger bulk-delete-btn" disabled
                        data-target="daily" data-url="{{ route('invoices.items.bulk-destroy', $invoice) }}">
                    <i class="bi bi-trash ms-1"></i> {{ __('Delete Selected') }}
                </button>
            </div>
            @endcanany
            @endif
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @if($isDraft)
                            <th style="width:30px;">
                                @canany(['add_invoice_items', 'edit_invoices'])
                                <input type="checkbox" class="form-check-input select-all" data-target="daily">
                                @endcanany
                            </th>
                            @endif
                            <th class="text-center" style="width:36px;">م</th>
                            <th style="width:110px;">{{ __('Category') }}</th>
                            <th class="text-end" style="width:55px;">{{ __('Qty') }}</th>
                            <th class="text-end" style="width:100px;">{{ __('Unit Price') }}</th>
                            <th class="text-end" style="width:100px;">{{ __('Total') }}</th>
                            <th>{{ __('Notes') }}</th>
                            @if($isDraft) <th style="width:60px;"></th> @endif
                        </tr>
                    </thead>
                    <tbody id="tbody-daily">
                        @php $catNo = 1; @endphp
                        @forelse ($dailyCategoryGroups as $group)
                        @php $count = $group['items']->count(); $isFirst = true; @endphp
                        @foreach ($group['items'] as $item)
                        <tr id="item-daily-{{ $item->id }}" data-cat-id="{{ $group['id'] }}">
                            @if($isDraft)
                            <td>
                                @canany(['add_invoice_items', 'edit_invoices'])
                                <input type="checkbox" class="form-check-input row-check" data-target="daily"
                                       data-type="{{ $item->isSingle ? 'item' : 'service' }}"
                                       value="{{ $item->isSingle ? $item->singleItem->id : $item->itemable->id }}">
                                @endcanany
                            </td>
                            @endif
                            @if($isFirst)
                            <td rowspan="{{ $count }}"
                                class="text-center fw-bold align-middle"
                                style="background:#f0f4fa; border-right:3px solid #1a3c6e; color:#1a3c6e;">{{ $catNo }}</td>
                            <td rowspan="{{ $count }}"
                                class="fw-semibold align-middle small"
                                style="color:#1a3c6e;">{{ $group['name'] }}</td>
                            @php $isFirst = false; @endphp
                            @endif
                            <td class="text-end">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-medium">{{ number_format($item->total, 2) }}</td>
                            <td class="small">
                                @if($item->itemable?->code)
                                    <span class="font-monospace text-muted fw-semibold">{{ $item->itemable->code }}</span>
                                    <span class="text-muted mx-1">—</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ __('No HIO code set for this item') }}">{{ __('No code') }}</span>
                                @endif
                                {{ $item->itemable->name ?? '—' }}
                            </td>
                            @if($isDraft)
                            <td class="text-end" style="white-space:nowrap;">
                                @canany(['add_invoice_items', 'edit_invoices'])
                                @if($item->isSingle)
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"
                                        data-bs-toggle="modal" data-bs-target="#editItemModal"
                                        data-item-name="{{ $item->itemable->name ?? '' }}"
                                        data-item-qty="{{ $item->qty }}"
                                        data-item-price="{{ $item->unit_price }}"
                                        data-item-url="{{ route('invoices.items.update', [$invoice, $item->singleItem]) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('invoices.items.destroy', [$invoice, $item->singleItem]) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('Remove this item?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger border-0 p-0 px-1">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                @else
                                {{-- Aggregated (multi-day) item: edit unit_price across all daily records --}}
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"
                                        data-bs-toggle="modal" data-bs-target="#editItemModal"
                                        data-item-bulk="1"
                                        data-item-name="{{ $item->itemable->name ?? '' }}"
                                        data-item-qty="{{ $item->qty }}"
                                        data-item-price="{{ $item->unit_price }}"
                                        data-item-url="{{ route('invoices.service-items.update', [$invoice, $item->itemable->id]) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('invoices.service-items.destroy', [$invoice, $item->itemable->id]) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('Remove all charges for this item?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger border-0 p-0 px-1">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                @endif
                                @endcanany
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        @php $catNo++; @endphp
                        @empty
                        <tr id="empty-daily">
                            <td colspan="{{ $isDraft ? 8 : 6 }}" class="text-muted small fst-italic py-3 text-center">
                                {{ __('No items in this section.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    {{-- ── أخرى — last group inside the same table ── --}}
                    @php $otherItems = $grouped['other'] ?? collect(); @endphp
                    <tbody id="tbody-other-header" style="{{ $otherItems->isEmpty() ? 'display:none' : '' }}">
                        <tr>
                            @if($isDraft) <td></td> @endif
                            <td class="text-center fw-bold align-middle"
                                style="background:#f0f4fa; border-right:3px solid #6c757d; color:#6c757d;"
                                id="other-cat-num">{{ $catNo }}</td>
                            <td class="fw-semibold align-middle small" style="color:#6c757d;"
                                colspan="{{ $isDraft ? 5 : 4 }}">{{ __('Other') }}</td>
                        </tr>
                    </tbody>
                    <tbody id="tbody-other">
                        @foreach($otherItems as $item)
                        <tr id="item-other-{{ $item->id }}">
                            @if($isDraft)
                            <td>
                                @canany(['add_invoice_items', 'edit_invoices'])
                                <input type="checkbox" class="form-check-input row-check" data-target="daily"
                                       data-type="item" value="{{ $item->id }}">
                                @endcanany
                            </td>
                            @endif
                            <td></td>
                            <td></td>
                            <td class="text-end">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-medium">{{ number_format($item->total, 2) }}</td>
                            <td class="small">
                                @if($item->itemable?->code)
                                    <span class="font-monospace text-muted fw-semibold">{{ $item->itemable->code }}</span>
                                    <span class="text-muted mx-1">—</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ __('No HIO code set for this item') }}">{{ __('No code') }}</span>
                                @endif
                                {{ $item->itemable->name ?? '—' }}
                            </td>
                            @if($isDraft)
                            <td class="text-end" style="white-space:nowrap;">
                                @canany(['add_invoice_items', 'edit_invoices'])
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"
                                        data-bs-toggle="modal" data-bs-target="#editItemModal"
                                        data-item-id="{{ $item->id }}"
                                        data-item-name="{{ $item->itemable->name ?? '' }}"
                                        data-item-qty="{{ $item->qty }}"
                                        data-item-price="{{ $item->unit_price }}"
                                        data-item-url="{{ route('invoices.items.update', [$invoice, $item]) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('invoices.items.destroy', [$invoice, $item]) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('Remove this item?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger border-0 p-0 px-1">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                @endcanany
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light {{ $allSvcItems->isEmpty() ? 'd-none' : '' }}" id="tfoot-daily">
                        <tr>
                            @if($isDraft) <td></td> @endif
                            <td colspan="4" class="text-end small fw-semibold">{{ __('Invoice Subtotal') }}</td>
                            <td class="text-end fw-bold" id="subtotal-daily">{{ number_format($allSvcItems->sum('total'), 2) }}</td>
                            <td colspan="{{ $isDraft ? 2 : 1 }}"></td>
                        </tr>
                    </tfoot>
                    @if($isDraft)
                    @canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
                    <tfoot>
                        <tr class="table-light">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><input type="number" class="form-control form-control-sm text-end"
                                       id="qty-other" value="1" min="1"></td>
                            <td><input type="number" class="form-control form-control-sm text-end"
                                       id="price-other" step="0.01" min="0" readonly placeholder="—"></td>
                            <td class="text-muted small fw-medium" id="preview-other">—</td>
                            <td>
                                <select class="form-select form-select-sm" id="select-other" data-section="other">
                                    <option value="">— {{ __('أخرى — اختر صنف') }} —</option>
                                </select>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary add-item-btn"
                                        data-section="other"
                                        data-url="{{ route('invoices.items.store', $invoice) }}">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    @endcanany
                    @endif
                </table>
            </div>
        </div>

        {{-- ── Individual section tabs ── --}}
        @foreach ($sections as $sectionKey => $meta)
        @php
            $rawItems = $grouped[$sectionKey] ?? collect();
            // Aggregate duplicate entries for the same item into one row
            $items = $rawItems
                ->groupBy('itemable_id')
                ->map(function ($rows) {
                    $first           = $rows->first();
                    $agg             = new \stdClass;
                    $agg->id         = $first->id;
                    $agg->itemable   = $first->itemable;
                    $agg->qty        = $rows->sum('qty');
                    $agg->perDayQty  = (int) $first->qty;
                    $agg->unit_price = (float) $first->unit_price;
                    $agg->total      = (float) $rows->sum('total');
                    $agg->section    = $first->section;
                    $agg->isSingle   = $rows->count() === 1;
                    $agg->singleItem = $rows->count() === 1 ? $first : null;
                    return $agg;
                })
                ->sortBy(fn ($agg) => $agg->itemable?->name ?? '')
                ->values();
        @endphp
        <div class="tab-pane fade p-0" id="tab-{{ $sectionKey }}" role="tabpanel">
            @if($isDraft)
            @canany(['add_invoice_items', 'edit_invoices'])
            <div class="d-flex justify-content-end p-2 border-bottom bg-light bg-opacity-50">
                <button type="button" class="btn btn-sm btn-outline-danger bulk-delete-btn" disabled
                        data-target="{{ $sectionKey }}" data-url="{{ route('invoices.items.bulk-destroy', $invoice) }}">
                    <i class="bi bi-trash ms-1"></i> {{ __('Delete Selected') }}
                </button>
            </div>
            @endcanany
            @endif
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @if($isDraft)
                            <th style="width:30px;">
                                @canany(['add_invoice_items', 'edit_invoices'])
                                <input type="checkbox" class="form-check-input select-all" data-target="{{ $sectionKey }}">
                                @endcanany
                            </th>
                            @endif
                            <th>{{ __('Item') }}</th>
                            <th class="text-end" style="width:80px;">{{ __('Qty') }}</th>
                            <th class="text-end" style="width:120px;">{{ __('Unit Price') }}</th>
                            <th class="text-end" style="width:120px;">{{ __('Total') }}</th>
                            @if($isDraft) <th style="width:60px;"></th> @endif
                        </tr>
                    </thead>
                    <tbody id="tbody-{{ $sectionKey }}">
                        @forelse ($items as $item)
                        <tr id="item-{{ $item->id }}">
                            @if($isDraft)
                            <td>
                                @canany(['add_invoice_items', 'edit_invoices'])
                                <input type="checkbox" class="form-check-input row-check" data-target="{{ $sectionKey }}"
                                       data-type="{{ $item->isSingle ? 'item' : 'service' }}"
                                       value="{{ $item->isSingle ? $item->singleItem->id : $item->itemable->id }}">
                                @endcanany
                            </td>
                            @endif
                            <td>
                                @if($item->itemable?->code)
                                    <span class="font-monospace text-muted fw-semibold small">{{ $item->itemable->code }}</span>
                                    <span class="text-muted mx-1">—</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ __('No HIO code set for this item') }}">{{ __('No code') }}</span>
                                @endif
                                <span class="fw-medium">{{ $item->itemable->name ?? '—' }}</span>
                                @if($sectionKey === 'local_med' || $sectionKey === 'imported_med')
                                    <span class="text-muted small ms-1">{{ $item->itemable->unit ?? '' }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-medium">{{ number_format($item->total, 2) }}</td>
                            @if($isDraft)
                            <td class="text-end" style="white-space:nowrap;">
                                @canany(['add_invoice_items', 'edit_invoices'])
                                @if($item->isSingle)
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"
                                        data-bs-toggle="modal" data-bs-target="#editItemModal"
                                        data-item-name="{{ $item->itemable->name ?? '' }}"
                                        data-item-qty="{{ $item->qty }}"
                                        data-item-price="{{ $item->unit_price }}"
                                        data-item-url="{{ route('invoices.items.update', [$invoice, $item->singleItem]) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('invoices.items.destroy', [$invoice, $item->singleItem]) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('Remove this item?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger border-0 p-0 px-1">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                @else
                                {{-- Aggregated (multiple records): edit unit_price across all --}}
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"
                                        data-bs-toggle="modal" data-bs-target="#editItemModal"
                                        data-item-bulk="1"
                                        data-item-name="{{ $item->itemable->name ?? '' }}"
                                        data-item-qty="{{ $item->qty }}"
                                        data-item-price="{{ $item->unit_price }}"
                                        data-item-url="{{ route('invoices.service-items.update', [$invoice, $item->itemable]) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('invoices.service-items.destroy', [$invoice, $item->itemable]) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('Remove all charges for this item?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger border-0 p-0 px-1">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                @endif
                                @endcanany
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr id="empty-{{ $sectionKey }}">
                            <td colspan="{{ $isDraft ? 6 : 4 }}" class="text-muted small fst-italic py-3 text-center">
                                {{ __('No items in this section.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light {{ $rawItems->isEmpty() ? 'd-none' : '' }}" id="tfoot-{{ $sectionKey }}">
                        <tr>
                            @if($isDraft) <td></td> @endif
                            <td colspan="{{ $isDraft ? 3 : 2 }}" class="text-end small fw-semibold">{{ $meta['subtotal_label'] }}</td>
                            <td class="text-end fw-bold" id="subtotal-{{ $sectionKey }}">{{ number_format($rawItems->sum('total'), 2) }}</td>
                            @if($isDraft) <td></td> @endif
                        </tr>
                    </tfoot>
                    @if($isDraft)
                    @canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
                    <tfoot>
                        <tr class="table-light">
                            <td></td>
                            <td>
                                <select class="form-select form-select-sm"
                                        id="select-{{ $sectionKey }}" data-section="{{ $sectionKey }}" data-prefix="">
                                    <option value="">— {{ __('Select item —') }} —</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control form-control-sm text-end"
                                       id="qty-{{ $sectionKey }}" value="1" min="1"></td>
                            <td><input type="number" class="form-control form-control-sm text-end"
                                       id="price-{{ $sectionKey }}" step="0.01" min="0" readonly placeholder="—"></td>
                            <td class="text-end text-muted small fw-medium" id="preview-{{ $sectionKey }}">—</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary add-item-btn"
                                        data-section="{{ $sectionKey }}" data-prefix=""
                                        data-url="{{ route('invoices.items.store', $invoice) }}">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    @endcanany
                    @endif
                </table>
            </div>
        </div>
        @endforeach
    </div>

    @php
        $dailyItems = $allSvcItems;
        $medDiscounts = $invoice->medicationDiscountedSubtotals();
    @endphp

    {{-- Grand total --}}
    <style>
        .gt-table td { border-bottom: 0; }
        .gt-table tr + tr td { border-top: 1px solid #dee2e6; }
    </style>
    <div class="card-body py-3">
        <div class="row justify-content-start">
            <div class="col-md-5">
                <table class="table table-sm mb-0 gt-table">
                    @if($dailyItems->isNotEmpty())
                    <tr>
                        <td class="text-muted small">{{ __('Daily Charges') }}</td>
                        <td class="text-end fw-medium">{{ number_format($dailyItems->sum('total'), 2) }}</td>
                    </tr>
                    @endif
                    @foreach ($sections as $sectionKey => $meta)
                    @if(in_array($sectionKey, ['local_med', 'imported_med']))
                        @php
                            $isLocal = $sectionKey === 'local_med';
                            $raw     = $isLocal ? $medDiscounts['local_raw']    : $medDiscounts['imported_raw'];
                            $pct     = $isLocal ? $medDiscounts['local_discount_pct'] : $medDiscounts['imported_discount_pct'];
                            $after   = $isLocal ? $medDiscounts['local_after'] : $medDiscounts['imported_after'];
                        @endphp
                        @if($raw > 0)
                        <tr>
                            <td class="text-muted small">
                                {{ $meta['subtotal_label'] }}
                                @if($pct > 0)<br><span class="text-muted" style="font-size: .75rem;">{{ __('after :pct% discount', ['pct' => number_format($pct, 0)]) }}</span>@endif
                            </td>
                            <td class="text-end">
                                @if($pct > 0)
                                <div class="text-muted text-decoration-line-through" style="font-size: .75rem;">{{ number_format($raw, 2) }}</div>
                                <div class="fw-medium text-success">{{ number_format($after, 2) }}</div>
                                @else
                                <div class="fw-medium">{{ number_format($after, 2) }}</div>
                                @endif
                            </td>
                        </tr>
                        @endif
                    @else
                    @php $sectionTotal = ($grouped[$sectionKey] ?? collect())->sum('total'); @endphp
                    @if($sectionTotal > 0)
                    <tr>
                        <td class="text-muted small">{{ $meta['subtotal_label'] }}</td>
                        <td class="text-end fw-medium">{{ number_format($sectionTotal, 2) }}</td>
                    </tr>
                    @endif
                    @endif
                    @endforeach
                    <tr class="border-top">
                        <td class="fw-bold pt-2 border-0">{{ __('GRAND TOTAL') }}</td>
                        <td class="text-end fw-bold fs-5 pt-2 border-0" id="grand-total-display">
                            {{ number_format($invoice->total_amount, 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ── Inline Add AJAX Script ───────────────────────────────────────────── --}}
@if($isDraft)
@canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
    .ts-wrapper .ts-control { direction: rtl; text-align: right; }
    .ts-dropdown            { direction: rtl; text-align: right; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="select-"]').forEach(function (el) {
        new TomSelect(el, {
            placeholder: el.options[0]?.text || '',
            maxOptions: null,
            highlight: true,
            dropdownParent: document.body,
            onDropdownOpen: function (dropdown) {
                var self = this;
                requestAnimationFrame(function () {
                    var dropRect    = dropdown.getBoundingClientRect();
                    var ctrlRect    = self.control.getBoundingClientRect();
                    var scrollY     = window.scrollY || document.documentElement.scrollTop;
                    var overflowsBy = dropRect.bottom - (window.innerHeight - 8);
                    if (overflowsBy > 0) {
                        // Flip above the control
                        dropdown.style.top = (ctrlRect.top + scrollY - dropdown.offsetHeight) + 'px';
                    }
                });
            },
        });
    });
});
</script>
@endpush
<script>
(function () {
    const CATALOG = {!! $catalogJson !!};
    const CSRF    = document.querySelector('meta[name="csrf-token"]').content;
    const SECTION_TYPE = { local_med: 'medication', imported_med: 'medication', supplies: 'supplies', lab: 'lab', radiology: 'radiology', other: 'other' };
    const WITH_UNIT    = { local_med: true, imported_med: true };
    const CONFIRM_MSG  = '{{ __('Remove this item?') }}';

    // Populate all selects — label includes code for TomSelect search
    Object.keys(SECTION_TYPE).forEach(function (section) {
        const sel = document.getElementById('select-' + section);
        if (!sel) return;
        (CATALOG[section] || []).forEach(function (item) {
            let label = item.name;
            if (item.unit)  label += ' (' + item.unit + ')';
            if (item.code)  label = item.code + ' — ' + label;
            sel.insertAdjacentHTML('beforeend',
                '<option value="' + item.id + '" data-price="' + item.price + '">' + label + '</option>');
        });
    });

    // Wire select / qty / price → preview
    function wirePreview(selEl) {
        const section = selEl.id.replace(/^select-/, '');
        const priceEl = document.getElementById('price-'   + section);
        const preEl   = document.getElementById('preview-' + section);
        const qtyEl   = document.getElementById('qty-'     + section);

        selEl.addEventListener('change', function () {
            const price = this.options[this.selectedIndex]?.dataset?.price;
            if (price) {
                priceEl.value     = parseFloat(price).toFixed(2);
                priceEl.readOnly  = false;
                preEl.textContent = ((parseFloat(qtyEl.value) || 1) * parseFloat(price)).toFixed(2);
            } else {
                priceEl.value = ''; priceEl.readOnly = true; preEl.textContent = '—';
            }
        });
        [qtyEl, priceEl].forEach(function (el) {
            if (!el) return;
            el.addEventListener('input', function () {
                const q = parseFloat(qtyEl.value) || 0, p = parseFloat(priceEl.value) || 0;
                preEl.textContent = (q > 0 && p > 0) ? (q * p).toFixed(2) : '—';
            });
        });
    }
    document.querySelectorAll('[id^="select-"]').forEach(wirePreview);

    // Build a table row — daily table has an extra Date column
    function buildRow(d, section) {
        const nameHtml = WITH_UNIT[section] && d.unit
            ? d.name + ' <span class="text-muted small ms-1">' + d.unit + '</span>' : d.name;
        const editBtn =
            '<button type="button" class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"' +
            ' data-bs-toggle="modal" data-bs-target="#editItemModal"' +
            ' data-item-id="' + d.id + '" data-item-name="' + d.name + '"' +
            ' data-item-qty="' + d.qty + '" data-item-price="' + d.unit_price + '"' +
            ' data-item-url="' + d.update_url + '"><i class="bi bi-pencil"></i></button>';
        const delForm =
            '<form method="POST" action="' + d.destroy_url + '" class="d-inline"' +
            ' onsubmit="return confirm(\'' + CONFIRM_MSG + '\')">' +
            '<input type="hidden" name="_token" value="' + CSRF + '">' +
            '<input type="hidden" name="_method" value="DELETE">' +
            '<button class="btn btn-xs btn-outline-danger border-0 p-0 px-1"><i class="bi bi-x-lg"></i></button>' +
            '</form>';

        // Daily table: م | Category | Qty | Unit Price | Total | Notes | Actions
        // Category cells are handled by insertItem (extends existing rowspan or creates new group).
        // buildRow only returns the data cells (no м/category cols).
        if (section === 'daily') {
            return '<td class="text-end">' + d.qty + '</td>' +
                '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
                '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>' +
                '<td class="small">' + nameHtml + '</td>' +
                '<td class="text-end" style="white-space:nowrap;">' + editBtn + delForm + '</td>';
        }
        if (section === 'other') {
            return '<td></td><td></td>' +
                '<td class="text-end">' + d.qty + '</td>' +
                '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
                '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>' +
                '<td class="small">' + nameHtml + '</td>' +
                '<td class="text-end" style="white-space:nowrap;">' + editBtn + delForm + '</td>';
        }
        const cells = '<td><span class="fw-medium">' + nameHtml + '</span></td>';
        return cells +
            '<td class="text-end">' + d.qty + '</td>' +
            '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
            '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>' +
            '<td class="text-end" style="white-space:nowrap;">' + editBtn + delForm + '</td>';
    }

    // Add button → AJAX POST
    document.querySelectorAll('.add-item-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const section = this.dataset.section;
            const url     = this.dataset.url;
            const selEl   = document.getElementById('select-' + section);
            const qtyEl   = document.getElementById('qty-'    + section);
            const priceEl = document.getElementById('price-'  + section);

            if (!selEl.value || !parseFloat(qtyEl.value) || !parseFloat(priceEl.value)) return;

            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const res  = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ item_type: SECTION_TYPE[section], itemable_id: selEl.value,
                                          qty: parseInt(qtyEl.value), unit_price: parseFloat(priceEl.value) })
                });
                const data = await res.json();
                if (!res.ok) { alert(data.error || 'Error'); return; }

                // Row already existed for this item — patch qty/total in place instead
                // of inserting a duplicate row (server merged the new qty into it).
                function qtyTotalCellIndices(ts, row) {
                    if (ts === 'other') return { qty: 2, total: 4 };
                    if (ts === 'daily') {
                        const hasCatCells = row.querySelectorAll('td[rowspan]').length > 0;
                        return hasCatCells ? { qty: 2, total: 4 } : { qty: 0, total: 2 };
                    }
                    return { qty: 1, total: 3 }; // local_med, imported_med, supplies, lab
                }

                function patchMergedItem(d, ts) {
                    const row = document.getElementById('item-' + d.id) ||
                                document.getElementById('item-' + ts + '-' + d.id);
                    if (row) {
                        const idx   = qtyTotalCellIndices(ts, row);
                        const cells = row.querySelectorAll('td');
                        if (cells[idx.qty])   cells[idx.qty].textContent   = d.qty;
                        if (cells[idx.total]) cells[idx.total].textContent = parseFloat(d.total).toFixed(2);
                        const editBtn = row.querySelector('[data-item-qty]');
                        if (editBtn) {
                            editBtn.dataset.itemQty   = d.qty;
                            editBtn.dataset.itemPrice = d.unit_price;
                        }
                    }
                    const sub = document.getElementById('subtotal-' + ts);
                    if (sub && d.delta_total) {
                        const prev = parseFloat(sub.textContent.replace(/,/g, '')) || 0;
                        sub.textContent = (prev + d.delta_total)
                            .toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
                    }
                }

                // Insert one item into its section tbody and update subtotal + badge
                function insertItem(d) {
                    const ts = d.section || section;
                    if (d.merged) { patchMergedItem(d, ts); return; }
                    const tbody = document.getElementById('tbody-' + ts);
                    if (!tbody) return;
                    const emptyRow = document.getElementById('empty-' + ts);
                    if (emptyRow) emptyRow.remove();

                    if (ts === 'daily' && d.category_id) {
                        // Find existing rows for this category group
                        const catRows = tbody.querySelectorAll('tr[data-cat-id="' + d.category_id + '"]');
                        if (catRows.length > 0) {
                            // Extend the rowspan of the category header cells (first row)
                            catRows[0].querySelectorAll('td[rowspan]').forEach(function (cell) {
                                cell.rowSpan = (parseInt(cell.rowSpan) || 1) + 1;
                            });
                            // Insert after the last row in this group (no category cells)
                            catRows[catRows.length - 1].insertAdjacentHTML('afterend',
                                '<tr id="item-daily-' + d.id + '" data-cat-id="' + d.category_id + '">' +
                                buildRow(d, 'daily') + '</tr>');
                        } else {
                            // Category doesn't exist yet — create a new group with category cells
                            tbody.insertAdjacentHTML('beforeend',
                                '<tr id="item-daily-' + d.id + '" data-cat-id="' + d.category_id + '">' +
                                '<td class="text-center fw-bold align-middle" style="background:#f0f4fa;border-right:3px solid #1a3c6e;color:#1a3c6e;">—</td>' +
                                '<td class="fw-semibold align-middle small" style="color:#1a3c6e;">' + (d.category_name || '—') + '</td>' +
                                buildRow(d, 'daily') + '</tr>');
                        }
                    } else {
                        // Show the "أخرى" header row when the first other item is added
                        if (ts === 'other') {
                            const hdr = document.getElementById('tbody-other-header');
                            if (hdr) hdr.style.display = '';
                        }
                        tbody.insertAdjacentHTML('beforeend',
                            '<tr id="item-' + ts + '-' + d.id + '">' + buildRow(d, ts) + '</tr>');
                    }

                    const tf = document.getElementById('tfoot-' + ts);
                    if (tf) tf.classList.remove('d-none');
                    const sub = document.getElementById('subtotal-' + ts);
                    if (sub) {
                        const prev = parseFloat(sub.textContent.replace(/,/g, '')) || 0;
                        sub.textContent = (prev + parseFloat(d.total))
                            .toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
                    }
                    const badge = document.getElementById('badge-' + ts);
                    if (badge) { badge.textContent = (parseInt(badge.textContent)||0)+1; badge.classList.remove('d-none'); }
                }

                insertItem(data.item);
                (data.triggered_items || []).forEach(insertItem);

                // Grand total
                const gt = document.getElementById('grand-total-display');
                if (gt) gt.textContent = parseFloat(data.invoice_total)
                    .toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});

                // Reset add row (clear TomSelect widget if active)
                if (selEl.tomselect) selEl.tomselect.clear();
                else selEl.value = '';
                qtyEl.value = 1;
                priceEl.value = ''; priceEl.readOnly = true;
                document.getElementById('preview-' + section).textContent = '—';

            } catch (e) { alert('Error'); }
            finally { btn.disabled = false; btn.innerHTML = '<i class="bi bi-plus-lg"></i>'; }
        });
    });
}());
</script>

{{-- ── Bulk import JS ───────────────────────────────────────────────────── --}}
<script>
(function () {
    const bulkBtn    = document.getElementById('bulkImportBtn');
    const fileInput  = document.getElementById('bulkFileInput');
    const resultDiv  = document.getElementById('bulkResult');
    if (!bulkBtn) return;

    const BULK_URL = '{{ route('invoices.items.bulk', $invoice) }}';
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;

    bulkBtn.addEventListener('click', async function () {
        const file = fileInput.files[0];
        if (!file) return;

        bulkBtn.disabled  = true;
        bulkBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        resultDiv.innerHTML = '';

        try {
            const formData = new FormData();
            formData.append('file', file);

            const res  = await fetch(BULK_URL, {
                method:  'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body:    formData,
            });
            const data = await res.json();
            if (!res.ok) { resultDiv.innerHTML = '<div class="alert alert-danger py-1 small">' + (data.error || 'Error') + '</div>'; return; }

            // Inject added rows into the correct tbodies
            (data.added || []).forEach(function (d) {
                var sec   = d.section;
                var tbody = document.getElementById('tbody-' + sec);
                if (!tbody) return;

                var empty = document.getElementById('empty-' + sec);
                if (empty) empty.remove();

                var nameHtml = d.name + (d.unit ? ' <span class="text-muted small ms-1">' + d.unit + '</span>' : '');
                var editBtn  = '<button type="button" class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"'
                    + ' data-bs-toggle="modal" data-bs-target="#editItemModal"'
                    + ' data-item-id="' + d.id + '" data-item-name="' + d.name + '"'
                    + ' data-item-qty="' + d.qty + '" data-item-price="' + d.unit_price + '"'
                    + ' data-item-url="' + d.update_url + '"><i class="bi bi-pencil"></i></button>';
                var delForm  = '<form method="POST" action="' + d.destroy_url + '" class="d-inline"'
                    + ' onsubmit="return confirm(\'{{ __("Remove this item?") }}\')"><input type="hidden" name="_token" value="' + CSRF + '">'
                    + '<input type="hidden" name="_method" value="DELETE">'
                    + '<button class="btn btn-xs btn-outline-danger border-0 p-0 px-1"><i class="bi bi-x-lg"></i></button></form>';

                tbody.insertAdjacentHTML('beforeend',
                    '<tr id="item-' + sec + '-' + d.id + '">'
                    + '<td><span class="fw-medium">' + nameHtml + '</span></td>'
                    + '<td class="text-end">' + d.qty + '</td>'
                    + '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>'
                    + '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>'
                    + '<td class="text-end">' + editBtn + delForm + '</td>'
                    + '</tr>');

                var tf = document.getElementById('tfoot-' + sec);
                if (tf) tf.classList.remove('d-none');

                var sub = document.getElementById('subtotal-' + sec);
                if (sub) {
                    var prev = parseFloat(sub.textContent.replace(/,/g, '')) || 0;
                    sub.textContent = (prev + parseFloat(d.total)).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
                }

                var badge = document.getElementById('badge-' + sec);
                if (badge) { badge.textContent = (parseInt(badge.textContent) || 0) + 1; badge.classList.remove('d-none'); }
            });

            // Update existing rows (qty bumped on server — patch qty + total cells in-place)
            (data.updated || []).forEach(function (d) {
                // Row id pattern for section tabs: "item-{id}" (first item id in the group)
                var row = document.getElementById('item-' + d.id);
                if (row) {
                    // Section tab columns: 0=name, 1=qty, 2=unit_price, 3=total, 4=actions
                    var cells = row.querySelectorAll('td');
                    if (cells[1]) cells[1].textContent = d.qty;
                    if (cells[3]) cells[3].textContent = parseFloat(d.total).toFixed(2);
                    // Sync edit-button data so the modal opens with the new qty
                    var editBtn = row.querySelector('[data-item-qty]');
                    if (editBtn) editBtn.dataset.itemQty = d.qty;
                }
                // Update subtotal by the delta
                var sub = document.getElementById('subtotal-' + d.section);
                if (sub && d.delta_total) {
                    var prev = parseFloat(sub.textContent.replace(/,/g, '')) || 0;
                    sub.textContent = (prev + d.delta_total).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
                }
            });

            // Grand total
            var gt = document.getElementById('grand-total-display');
            if (gt) gt.textContent = parseFloat(data.invoice_total).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});

            // Summary
            var html = '';
            if (data.added.length || (data.updated && data.updated.length)) {
                var allDone = (data.added || []).concat(data.updated || []);
                html += '<div class="alert alert-success py-2 small mb-1">'
                    + '<i class="bi bi-check-circle ms-1"></i> '
                    + '{{ __("Added") }}: <strong>' + allDone.length + '</strong> '
                    + allDone.map(function (d) { return d.name + ' ×' + d.qty; }).join(' — ')
                    + '</div>';
            }
            if (data.not_found.length) {
                html += '<div class="alert alert-warning py-2 small mb-0">'
                    + '<i class="bi bi-exclamation-triangle ms-1"></i> '
                    + '{{ __("Not found") }}: '
                    + data.not_found.map(function (r) { return r.name || r.code; }).join(' — ')
                    + '</div>';
            }
            resultDiv.innerHTML = html;
            if (data.added.length || (data.updated && data.updated.length)) fileInput.value = '';

        } catch (e) { resultDiv.innerHTML = '<div class="alert alert-danger py-1 small">Error</div>'; }
        finally { bulkBtn.disabled = false; bulkBtn.innerHTML = '<i class="bi bi-check2-all ms-1"></i> {{ __("Add to Invoice") }}'; }
    });
}());
</script>
@endcanany

{{-- ── Edit Item Modal ─────────────────────────────────────────────────── --}}
@canany(['add_invoice_items', 'edit_invoices'])
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editItemForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">
                        <i class="bi bi-pencil ms-1 text-primary"></i> {{ __('Edit Item') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-medium mb-3" id="editItemName"></p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="edit_qty">{{ __('Qty') }} <span class="text-danger">*</span></label>
                            <input id="edit_qty" type="number" name="qty" class="form-control" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit_unit_price">{{ __('Unit Price') }} <span class="text-danger">*</span></label>
                            <input id="edit_unit_price" type="number" name="unit_price" step="0.01" min="0" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3 text-muted small" id="edit-line-total"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg ms-1"></i> {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('editItemModal').addEventListener('show.bs.modal', function (e) {
    const btn   = e.relatedTarget;
    const form  = document.getElementById('editItemForm');
    const qty   = document.getElementById('edit_qty');
    const price = document.getElementById('edit_unit_price');
    const total = document.getElementById('edit-line-total');
    const isBulk = btn.dataset.itemBulk === '1';

    form.action     = btn.dataset.itemUrl;
    document.getElementById('editItemName').textContent = btn.dataset.itemName;
    qty.value       = btn.dataset.itemQty;
    price.value     = parseFloat(btn.dataset.itemPrice).toFixed(2);
    qty.readOnly    = false;
    qty.closest('.col-6').querySelector('.form-label').textContent =
        isBulk ? 'الكمية الإجمالية'
                : '{{ __('Qty') }} *';

    function updateTotal() {
        const q = parseFloat(qty.value) || 0;
        const p = parseFloat(price.value) || 0;
        total.textContent = q > 0 && p > 0 ? '{{ __('Line total:') }} ' + (q * p).toFixed(2) : '';
    }
    updateTotal();
    qty.oninput   = updateTotal;
    price.oninput = updateTotal;
});
</script>

{{-- ── Select-all + bulk delete (checkboxes in each tab) ───────────────── --}}
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const CONFIRM_MSG = '{{ __('Remove selected items?') }}';

    document.querySelectorAll('.bulk-delete-btn').forEach(function (btn) {
        const target = btn.dataset.target;
        const master = document.querySelector('.select-all[data-target="' + target + '"]');
        const boxes  = function () { return document.querySelectorAll('.row-check[data-target="' + target + '"]'); };

        function refreshBtn() {
            btn.disabled = !Array.from(boxes()).some(function (b) { return b.checked; });
        }

        if (master) {
            master.addEventListener('change', function () {
                boxes().forEach(function (b) { b.checked = master.checked; });
                refreshBtn();
            });
        }

        document.addEventListener('change', function (e) {
            if (e.target.matches('.row-check[data-target="' + target + '"]')) refreshBtn();
        });

        btn.addEventListener('click', async function () {
            const checked = Array.from(boxes()).filter(function (b) { return b.checked; });
            if (!checked.length || !confirm(CONFIRM_MSG)) return;

            const itemIds    = checked.filter(function (b) { return b.dataset.type === 'item'; }).map(function (b) { return b.value; });
            const serviceIds = checked.filter(function (b) { return b.dataset.type === 'service'; }).map(function (b) { return b.value; });

            btn.disabled = true;
            try {
                const res  = await fetch(btn.dataset.url, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ item_ids: itemIds, service_ids: serviceIds }),
                });
                const data = await res.json();
                if (!res.ok) { alert(data.error || 'Error'); btn.disabled = false; return; }
                location.reload();
            } catch (e) {
                alert('Error');
                btn.disabled = false;
            }
        });
    });
}());
</script>
@endcanany
@endif

@endsection
