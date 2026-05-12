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
<div class="card border-0 shadow-sm">

    {{-- Tab nav --}}
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
        <ul class="nav nav-tabs card-header-tabs" id="invoiceSectionTabs" role="tablist">
            @foreach ($sections as $sectionKey => $meta)
            @php $tabItems = $grouped[$sectionKey] ?? collect(); @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                        id="tab-{{ $sectionKey }}-btn"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-{{ $sectionKey }}"
                        type="button" role="tab">
                    {{ $meta['label'] }}
                    @if($tabItems->isNotEmpty())
                        <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $tabItems->count() }}</span>
                    @endif
                </button>
            </li>
            @endforeach
        </ul>
    </div>

    {{-- Tab content --}}
    <div class="tab-content border-bottom">
        @foreach ($sections as $sectionKey => $meta)
        @php $items = $grouped[$sectionKey] ?? collect(); @endphp
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }} p-3"
             id="tab-{{ $sectionKey }}" role="tabpanel">

            @if($items->isEmpty())
                <p class="text-muted small fst-italic mb-0 py-2">{{ __('No items in this section.') }}</p>
            @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th class="text-end" style="width:70px;">{{ __('Qty') }}</th>
                            <th class="text-end" style="width:120px;">{{ __('Unit Price') }}</th>
                            <th class="text-end" style="width:120px;">{{ __('Total') }}</th>
                            @if($isDraft) <th style="width:60px;"></th> @endif
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
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"
                                        title="{{ __('Edit') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editItemModal"
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
    </div>

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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('invoices.items.store', $invoice) }}" id="addItemForm">
                @csrf
                <input type="hidden" name="item_type" id="item_type_hidden" value="medication">

                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">
                        <i class="bi bi-plus-circle ms-1 text-primary"></i> {{ __('Add Invoice Item') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- Category Tabs --}}
                    <ul class="nav nav-tabs mb-3" id="addItemTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" type="button"
                                    data-tab-key="local_med" data-item-type="medication">
                                <i class="bi bi-capsule ms-1"></i> {{ __('Local Med') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button"
                                    data-tab-key="imported_med" data-item-type="medication">
                                <i class="bi bi-capsule-pill ms-1"></i> {{ __('Imported Med') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button"
                                    data-tab-key="lab" data-item-type="lab">
                                <i class="bi bi-eyedropper ms-1"></i> {{ __('Lab') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button"
                                    data-tab-key="radiology" data-item-type="radiology">
                                <i class="bi bi-radioactive ms-1"></i> {{ __('Radiology') }}
                            </button>
                        </li>
                    </ul>

                    {{-- Item select --}}
                    <div class="mb-3">
                        <label class="form-label" for="itemable_id">{{ __('Item') }} <span class="text-danger">*</span></label>
                        <select id="itemable_id" name="itemable_id" class="form-select" required>
                            <option value="">— {{ __('Select item —') }}</option>
                        </select>
                    </div>

                    {{-- Qty + Price --}}
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="qty">{{ __('Qty') }} <span class="text-danger">*</span></label>
                            <input id="qty" type="number" name="qty" value="1" class="form-control" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="unit_price">{{ __('Unit Price') }}</label>
                            <input id="unit_price" type="number" name="unit_price" step="0.01" min="0"
                                   class="form-control" required readonly>
                        </div>
                    </div>

                    {{-- Live line total --}}
                    <div class="mt-3 text-muted small" id="line-total-preview"></div>
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

    const itemTypeHidden = document.getElementById('item_type_hidden');
    const itemSelect     = document.getElementById('itemable_id');
    const priceInput     = document.getElementById('unit_price');
    const qtyInput       = document.getElementById('qty');
    const totalPreview   = document.getElementById('line-total-preview');
    const tabBtns        = document.querySelectorAll('#addItemTabs button');

    const noItemsLabel  = '{{ __('No items in catalog for this category') }}';
    const selectLabel   = '— {{ __('Select item —') }}';
    const lineTotalLabel = '{{ __('Line total:') }}';

    function updateTotal() {
        const qty   = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        totalPreview.textContent = (qty > 0 && price > 0)
            ? lineTotalLabel + ' ' + (qty * price).toFixed(2)
            : '';
    }

    function loadTab(tabKey, itemType) {
        const items = CATALOG[tabKey] || [];
        itemTypeHidden.value = itemType;

        itemSelect.innerHTML = items.length
            ? `<option value="">${selectLabel}</option>`
            : `<option value="">${noItemsLabel}</option>`;

        items.forEach(function (item) {
            const label = item.unit ? `${item.name} (${item.unit})` : item.name;
            itemSelect.insertAdjacentHTML('beforeend',
                `<option value="${item.id}" data-price="${item.price}">${label}</option>`
            );
        });

        itemSelect.disabled  = items.length === 0;
        priceInput.value     = '';
        priceInput.readOnly  = true;
        totalPreview.textContent = '';
    }

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadTab(btn.dataset.tabKey, btn.dataset.itemType);
        });
    });

    itemSelect.addEventListener('change', function () {
        const price = this.options[this.selectedIndex]?.dataset?.price;
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

    document.getElementById('addItemModal').addEventListener('show.bs.modal', function () {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabBtns[0].classList.add('active');
        loadTab('local_med', 'medication');
        qtyInput.value = 1;
    });

    document.getElementById('addItemModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addItemForm').reset();
        priceInput.readOnly      = true;
        totalPreview.textContent = '';
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
