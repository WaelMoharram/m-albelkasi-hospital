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

    $sections = [
        'local_med'    => ['label' => __('Local Medications'),    'icon' => 'bi-capsule',        'color' => 'success'],
        'imported_med' => ['label' => __('Imported Medications'), 'icon' => 'bi-capsule-pill',    'color' => 'warning'],
        'supplies'     => ['label' => __('Supplies'),             'icon' => 'bi-box-seam',        'color' => 'secondary'],
        'lab'          => ['label' => __('Lab'),                   'icon' => 'bi-eyedropper',      'color' => 'info'],
        'radiology'    => ['label' => __('Radiology'),             'icon' => 'bi-radioactive',     'color' => 'purple'],
    ];

    $billableTotal = $invoice->items
        ->whereIn('section', array_keys($sections))
        ->sum('total');
@endphp

@section('content')

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

{{-- ── Patient / Admission header ──────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-semibold mb-1">{{ __('Patient') }}</div>
                <div class="fw-bold">{{ $patient->name }}</div>
                <div class="text-muted small font-monospace">{{ $patient->national_id }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-semibold mb-1">{{ __('Insurance') }}</div>
                <div>{{ $patient->insuranceCompany->name ?? '—' }}</div>
                @if($admission->referral_number)
                <div class="text-muted small">{{ __('Referral #') }}: {{ $admission->referral_number }}</div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-semibold mb-1">{{ __('Admission') }}</div>
                <div>
                    <a href="{{ route('admissions.show', $admission) }}" class="text-decoration-none">
                        #{{ $admission->id }}
                    </a>
                    — {{ $admission->admission_date->format('d/m/Y') }}
                    @if($admission->discharge_date)
                        ← {{ $admission->discharge_date->format('d/m/Y') }}
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">{{ __('Active') }}</span>
                    @endif
                </div>
                <div class="text-muted small">
                    {{ __('Room') }} {{ $admission->room ?? '—' }} / {{ $admission->ward ?? '—' }}
                </div>
            </div>
        </div>
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
                {{ __('Paste directly from Excel — columns: Item Name, Item Code, Qty (tab-separated). The system matches by code first, then by name.') }}
            </p>
            <textarea id="bulkPasteArea" class="form-control form-control-sm font-monospace mb-2"
                      rows="6" dir="rtl"
                      placeholder="{{ __('اتروفنت 500mcg/2ml امبول') }}&#9;40&#9;11&#10;{{ __('الدوميت 250مجم اقراص') }}&#9;1140&#9;2"></textarea>
            <div class="d-flex gap-2 align-items-center">
                <button id="bulkImportBtn" type="button" class="btn btn-sm btn-success">
                    <i class="bi bi-check2-all ms-1"></i> {{ __('Add to Invoice') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="document.getElementById('bulkPasteArea').value='';document.getElementById('bulkResult').innerHTML=''">
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
                    <span class="badge bg-secondary-subtle text-secondary ms-1 {{ $allSvcItems->isEmpty() ? 'd-none' : '' }}"
                          id="badge-daily">{{ $allSvcItems->count() }}</span>
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
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
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
                        <tr id="item-daily-{{ $item->id }}">
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
                                @endif
                                {{ $item->itemable->name ?? '—' }}
                            </td>
                            @if($isDraft)
                            <td class="text-end">
                                @canany(['add_invoice_items', 'edit_invoices'])
                                @if($item->isSingle)
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"
                                        data-bs-toggle="modal" data-bs-target="#editItemModal"
                                        data-item-id="{{ $item->singleItem->id }}"
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
                                @endif
                                @endcanany
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        @php $catNo++; @endphp
                        @empty
                        <tr id="empty-daily">
                            <td colspan="{{ $isDraft ? 7 : 6 }}" class="text-muted small fst-italic py-3 text-center">
                                {{ __('No items in this section.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    {{-- ── أخرى — last group inside the same table ── --}}
                    @php $otherItems = $grouped['other'] ?? collect(); @endphp
                    <tbody id="tbody-other-header" style="{{ $otherItems->isEmpty() ? 'display:none' : '' }}">
                        <tr>
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
                            <td></td>
                            <td></td>
                            <td class="text-end">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-medium">{{ number_format($item->total, 2) }}</td>
                            <td class="small">
                                @if($item->itemable?->code)
                                    <span class="font-monospace text-muted fw-semibold">{{ $item->itemable->code }}</span>
                                    <span class="text-muted mx-1">—</span>
                                @endif
                                {{ $item->itemable->name ?? '—' }}
                            </td>
                            @if($isDraft)
                            <td class="text-end">
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
                            <td colspan="4" class="text-end small fw-semibold">{{ __('Subtotal') }}</td>
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
        @php $items = $grouped[$sectionKey] ?? collect(); @endphp
        <div class="tab-pane fade p-0" id="tab-{{ $sectionKey }}" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
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
                            <td>
                                <span class="fw-medium">{{ $item->itemable->name ?? '—' }}</span>
                                @if($sectionKey === 'local_med' || $sectionKey === 'imported_med')
                                    <span class="text-muted small ms-1">{{ $item->itemable->unit ?? '' }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-medium">{{ number_format($item->total, 2) }}</td>
                            @if($isDraft)
                            <td class="text-end">
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
                        @empty
                        <tr id="empty-{{ $sectionKey }}">
                            <td colspan="{{ $isDraft ? 5 : 4 }}" class="text-muted small fst-italic py-3 text-center">
                                {{ __('No items in this section.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light {{ $items->isEmpty() ? 'd-none' : '' }}" id="tfoot-{{ $sectionKey }}">
                        <tr>
                            <td colspan="{{ $isDraft ? 3 : 2 }}" class="text-end small fw-semibold">{{ __('Subtotal') }}</td>
                            <td class="text-end fw-bold" id="subtotal-{{ $sectionKey }}">{{ number_format($items->sum('total'), 2) }}</td>
                            @if($isDraft) <td></td> @endif
                        </tr>
                    </tfoot>
                    @if($isDraft)
                    @canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
                    <tfoot>
                        <tr class="table-light">
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

    {{-- Daily + other services summary row --}}
    @php $dailyItems = $allSvcItems; @endphp
    @if($dailyItems->isNotEmpty())
    <div class="card-body border-bottom py-3 bg-light bg-opacity-50">
        <div class="d-flex align-items-center justify-content-between">
            <span class="small text-muted">
                <i class="bi bi-calendar-check ms-1"></i>
                {{ __('Daily Hospital Charges') }}
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1">
                    {{ $dailyItems->count() }} {{ __('entries') }}
                </span>
            </span>
            <span class="text-muted small">
                {{ __('Subtotal') }}: <strong class="text-dark">{{ number_format($dailyItems->sum('total'), 2) }}</strong>
            </span>
        </div>
    </div>
    @endif

    {{-- Grand total --}}
    <div class="card-body py-3">
        <div class="row justify-content-start">
            <div class="col-md-5">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted small border-0">{{ __('Medications & Services Subtotal') }}</td>
                        <td class="text-end border-0 fw-medium">{{ number_format($billableTotal, 2) }}</td>
                    </tr>
                    @if($dailyItems->isNotEmpty())
                    <tr>
                        <td class="text-muted small border-0">{{ __('Daily Charges') }}</td>
                        <td class="text-end border-0 fw-medium">{{ number_format($dailyItems->sum('total'), 2) }}</td>
                    </tr>
                    @endif
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

        if (section === 'other') {
            return '<td></td><td></td>' +
                '<td class="text-end">' + d.qty + '</td>' +
                '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
                '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>' +
                '<td class="small">' + nameHtml + '</td>' +
                '<td class="text-end">' + editBtn + delForm + '</td>';
        }
        const cells = '<td><span class="fw-medium">' + nameHtml + '</span></td>';
        if (section === 'daily') {
            return cells +
                '<td class="text-end text-muted small">—</td>' +
                '<td class="text-end">' + d.qty + '</td>' +
                '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
                '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>' +
                '<td class="text-end">' + editBtn + delForm + '</td>';
        }
        return cells +
            '<td class="text-end">' + d.qty + '</td>' +
            '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
            '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>' +
            '<td class="text-end">' + editBtn + delForm + '</td>';
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

                // Insert one item into its section tbody and update subtotal + badge
                function insertItem(d) {
                    const ts = d.section || section;
                    const tbody = document.getElementById('tbody-' + ts);
                    if (!tbody) return;
                    const emptyRow = document.getElementById('empty-' + ts);
                    if (emptyRow) emptyRow.remove();
                    // Show the "أخرى" header row when the first other item is added
                    if (ts === 'other') {
                        const hdr = document.getElementById('tbody-other-header');
                        if (hdr) hdr.style.display = '';
                    }
                    tbody.insertAdjacentHTML('beforeend',
                        '<tr id="item-' + ts + '-' + d.id + '">' + buildRow(d, ts) + '</tr>');
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
    const pasteArea  = document.getElementById('bulkPasteArea');
    const resultDiv  = document.getElementById('bulkResult');
    if (!bulkBtn) return;

    const BULK_URL = '{{ route('invoices.items.bulk', $invoice) }}';
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;

    function parseRows(text) {
        return text.trim().split('\n')
            .map(function (line) {
                var cols = line.split('\t').map(function (c) { return c.trim(); });
                return { name: cols[0] || '', code: cols[1] || '', qty: parseInt(cols[2]) || 1 };
            })
            .filter(function (r) { return r.name || r.code; });
    }

    bulkBtn.addEventListener('click', async function () {
        const rows = parseRows(pasteArea.value);
        if (!rows.length) return;

        bulkBtn.disabled  = true;
        bulkBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        resultDiv.innerHTML = '';

        try {
            const res  = await fetch(BULK_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body:    JSON.stringify({ rows }),
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

            // Grand total
            var gt = document.getElementById('grand-total-display');
            if (gt) gt.textContent = parseFloat(data.invoice_total).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});

            // Summary
            var html = '';
            if (data.added.length) {
                html += '<div class="alert alert-success py-2 small mb-1">'
                    + '<i class="bi bi-check-circle ms-1"></i> '
                    + '{{ __("Added") }}: <strong>' + data.added.length + '</strong> '
                    + data.added.map(function (d) { return d.name + ' ×' + d.qty; }).join(' — ')
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
            if (data.added.length) pasteArea.value = '';

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

    form.action = btn.dataset.itemUrl;
    document.getElementById('editItemName').textContent = btn.dataset.itemName;
    qty.value   = btn.dataset.itemQty;
    price.value = parseFloat(btn.dataset.itemPrice).toFixed(2);

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
@endcanany
@endif

@endsection
