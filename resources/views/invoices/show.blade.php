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

    $sections = [
        'local_med'    => ['label' => __('Local Medications'),    'icon' => 'bi-capsule',        'color' => 'success'],
        'imported_med' => ['label' => __('Imported Medications'), 'icon' => 'bi-capsule-pill',    'color' => 'warning'],
        'lab'          => ['label' => __('Lab Tests'),             'icon' => 'bi-eyedropper',      'color' => 'info'],
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
            @canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="bi bi-plus-lg ms-1"></i> {{ __('Add Item') }}
            </button>
            @endcanany
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
    </div>
</div>

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
                <div class="text-muted small">{{ __('Policy') }}: {{ $patient->policy_number }}</div>
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

{{-- ── 4 Invoice Sections ──────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">

    @foreach ($sections as $sectionKey => $meta)
    @php $items = $grouped[$sectionKey] ?? collect(); @endphp
    <div class="card-body border-bottom py-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-semibold text-{{ $meta['color'] }} mb-0">
                <i class="bi {{ $meta['icon'] }} ms-1"></i>{{ $meta['label'] }}
            </h6>
            @if($items->isNotEmpty())
                <span class="text-muted small">
                    {{ __('Subtotal') }}: <strong class="text-dark">{{ number_format($items->sum('total'), 2) }}</strong>
                </span>
            @endif
        </div>

        @if($items->isEmpty())
            <p class="text-muted small fst-italic mb-0">{{ __('No items in this section.') }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Item') }}</th>
                        <th class="text-end" style="width:70px;">{{ __('Qty') }}</th>
                        <th class="text-end" style="width:120px;">{{ __('Unit Price') }}</th>
                        <th class="text-end" style="width:120px;">{{ __('Total') }}</th>
                        @if($isDraft) <th style="width:40px;"></th> @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                    <tr>
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
                            <form method="POST"
                                  action="{{ route('invoices.items.destroy', [$invoice, $item]) }}"
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
                <tfoot class="table-light">
                    <tr>
                        <td colspan="{{ $isDraft ? 4 : 3 }}" class="text-end small fw-semibold">{{ __('Subtotal') }}</td>
                        <td class="text-end fw-bold">{{ number_format($items->sum('total'), 2) }}</td>
                        @if($isDraft) <td></td> @endif
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
    @endforeach

    {{-- Daily services summary row --}}
    @php $dailyItems = $grouped['daily'] ?? collect(); @endphp
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
                        <td class="text-end fw-bold fs-5 pt-2 border-0">
                            {{ number_format($invoice->total_amount, 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ── Add Item Modal ──────────────────────────────────────────────────── --}}
@if($isDraft)
@canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('invoices.items.store', $invoice) }}" id="addItemForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">
                        <i class="bi bi-plus-circle ms-1 text-primary"></i> {{ __('Add Invoice Item') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- Category --}}
                    <div class="mb-3">
                        <label class="form-label" for="item_type">{{ __('Category') }} <span class="text-danger">*</span></label>
                        <select id="item_type" name="item_type" class="form-select" required>
                            <option value="">— {{ __('Select category —') }}</option>
                            <option value="medication">{{ __('Medication') }}</option>
                            <option value="lab">{{ __('Lab Test') }}</option>
                            <option value="radiology">{{ __('Radiology') }}</option>
                        </select>
                    </div>

                    {{-- Item --}}
                    <div class="mb-3">
                        <label class="form-label" for="itemable_id">{{ __('Item') }} <span class="text-danger">*</span></label>
                        <select id="itemable_id" name="itemable_id" class="form-select" required disabled>
                            <option value="">— {{ __('Select category first —') }}</option>
                        </select>
                    </div>

                    {{-- Qty + Price --}}
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="qty">{{ __('Qty') }} <span class="text-danger">*</span></label>
                            <input id="qty" type="number" name="qty" value="1"
                                   class="form-control" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="unit_price">
                                {{ __('Unit Price') }}
                                <span class="text-muted small">({{ __('Auto-filled from National ID') }})</span>
                            </label>
                            <input id="unit_price" type="number" name="unit_price" step="0.01" min="0"
                                   class="form-control" required readonly>
                        </div>
                    </div>

                    {{-- Live line total --}}
                    <div class="mt-3 text-start text-muted small" id="line-total-preview"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg ms-1"></i> {{ __('Add Item') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const CATALOG = {!! $catalogJson !!};

    const typeSelect   = document.getElementById('item_type');
    const itemSelect   = document.getElementById('itemable_id');
    const priceInput   = document.getElementById('unit_price');
    const qtyInput     = document.getElementById('qty');
    const totalPreview = document.getElementById('line-total-preview');

    const noItemsLabel    = '{{ __('No items in catalog for this category') }}';
    const selectFirstLabel = '— {{ __('Select category first —') }}';
    const selectItemLabel  = '— {{ __('Select item —') }}';
    const lineTotalLabel   = '{{ __('Line total:') }}';

    function updateTotal() {
        const qty   = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        if (qty > 0 && price > 0) {
            totalPreview.textContent = lineTotalLabel + ' ' + (qty * price).toFixed(2);
        } else {
            totalPreview.textContent = '';
        }
    }

    typeSelect.addEventListener('change', function () {
        const type  = this.value;
        const items = CATALOG[type] || [];

        itemSelect.innerHTML = `<option value="">${selectItemLabel}</option>`;
        items.forEach(function (item) {
            const label = item.unit ? `${item.name} (${item.unit})` : item.name;
            itemSelect.insertAdjacentHTML('beforeend',
                `<option value="${item.id}" data-price="${item.price}">${label}</option>`
            );
        });

        itemSelect.disabled      = items.length === 0;
        priceInput.value         = '';
        priceInput.readOnly      = true;
        totalPreview.textContent = '';

        if (items.length === 0) {
            itemSelect.innerHTML = `<option value="">${noItemsLabel}</option>`;
        }
    });

    itemSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const price    = selected?.dataset?.price;
        if (price) {
            priceInput.value    = parseFloat(price).toFixed(2);
            priceInput.readOnly = false;
        } else {
            priceInput.value    = '';
            priceInput.readOnly = true;
        }
        updateTotal();
    });

    qtyInput.addEventListener('input', updateTotal);
    priceInput.addEventListener('input', updateTotal);

    document.getElementById('addItemModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addItemForm').reset();
        itemSelect.innerHTML     = `<option value="">${selectFirstLabel}</option>`;
        itemSelect.disabled      = true;
        priceInput.value         = '';
        priceInput.readOnly      = true;
        totalPreview.textContent = '';
    });
}());
</script>
@endcanany
@endif

@endsection
