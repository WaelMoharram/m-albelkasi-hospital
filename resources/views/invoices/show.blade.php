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
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }} p-0"
             id="tab-{{ $sectionKey }}" role="tabpanel">
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

                    {{-- Subtotal footer (hidden when empty) --}}
                    <tfoot class="table-light {{ $items->isEmpty() ? 'd-none' : '' }}" id="tfoot-{{ $sectionKey }}">
                        <tr>
                            <td colspan="{{ $isDraft ? 3 : 2 }}" class="text-end small fw-semibold">{{ __('Subtotal') }}</td>
                            <td class="text-end fw-bold" id="subtotal-{{ $sectionKey }}">{{ number_format($items->sum('total'), 2) }}</td>
                            @if($isDraft) <td></td> @endif
                        </tr>
                    </tfoot>

                    {{-- Inline add row (draft only) --}}
                    @if($isDraft)
                    @canany(['add_invoice_items', 'edit_invoices', 'create_invoices'])
                    <tfoot>
                        <tr class="table-warning">
                            <td>
                                <select class="form-select form-select-sm"
                                        id="select-{{ $sectionKey }}"
                                        data-section="{{ $sectionKey }}">
                                    <option value="">— {{ __('Select item —') }} —</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       id="qty-{{ $sectionKey }}"
                                       value="1" min="1">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end"
                                       id="price-{{ $sectionKey }}"
                                       step="0.01" min="0" readonly placeholder="—">
                            </td>
                            <td class="text-end text-muted small fw-medium" id="preview-{{ $sectionKey }}">—</td>
                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-primary add-item-btn"
                                        data-section="{{ $sectionKey }}"
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
<script>
(function () {
    const CATALOG = {!! $catalogJson !!};
    const CSRF    = document.querySelector('meta[name="csrf-token"]').content;
    const SECTION_TYPE = {
        local_med: 'medication', imported_med: 'medication',
        lab: 'lab', radiology: 'radiology'
    };
    const CONFIRM_MSG = '{{ __('Remove this item?') }}';

    // Populate selects from catalog
    Object.keys(SECTION_TYPE).forEach(function (section) {
        const select = document.getElementById('select-' + section);
        if (!select) return;
        (CATALOG[section] || []).forEach(function (item) {
            const label = item.unit ? item.name + ' (' + item.unit + ')' : item.name;
            select.insertAdjacentHTML('beforeend',
                '<option value="' + item.id + '" data-price="' + item.price + '">' + label + '</option>'
            );
        });
    });

    // Select change → auto-fill price + preview
    document.querySelectorAll('[id^="select-"]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const section = this.id.replace('select-', '');
            const price   = this.options[this.selectedIndex]?.dataset?.price;
            const priceEl = document.getElementById('price-' + section);
            const qty     = parseFloat(document.getElementById('qty-' + section).value) || 1;
            if (price) {
                priceEl.value    = parseFloat(price).toFixed(2);
                priceEl.readOnly = false;
                document.getElementById('preview-' + section).textContent = (qty * parseFloat(price)).toFixed(2);
            } else {
                priceEl.value    = '';
                priceEl.readOnly = true;
                document.getElementById('preview-' + section).textContent = '—';
            }
        });
    });

    // Qty / price change → update preview
    ['qty-', 'price-'].forEach(function (prefix) {
        document.querySelectorAll('[id^="' + prefix + '"]').forEach(function (inp) {
            inp.addEventListener('input', function () {
                const section = this.id.replace(prefix, '');
                const qty   = parseFloat(document.getElementById('qty-'   + section).value) || 0;
                const price = parseFloat(document.getElementById('price-' + section).value) || 0;
                document.getElementById('preview-' + section).textContent =
                    (qty > 0 && price > 0) ? (qty * price).toFixed(2) : '—';
            });
        });
    });

    // Add button → AJAX POST
    document.querySelectorAll('.add-item-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const section  = this.dataset.section;
            const url      = this.dataset.url;
            const select   = document.getElementById('select-'  + section);
            const qtyEl    = document.getElementById('qty-'     + section);
            const priceEl  = document.getElementById('price-'   + section);
            const itemableId = select.value;
            const qty        = parseInt(qtyEl.value);
            const unitPrice  = parseFloat(priceEl.value);

            if (!itemableId || !qty || !unitPrice) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const res  = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ item_type: SECTION_TYPE[section], itemable_id: itemableId, qty, unit_price: unitPrice })
                });
                const data = await res.json();
                if (!res.ok) { alert(data.error || 'Error'); return; }

                const d = data.item;

                // Remove empty-state row
                const emptyRow = document.getElementById('empty-' + section);
                if (emptyRow) emptyRow.remove();

                // Show subtotal footer
                const tfoot = document.getElementById('tfoot-' + section);
                if (tfoot) tfoot.classList.remove('d-none');

                // Append new item row
                const nameHtml = (section === 'local_med' || section === 'imported_med') && d.unit
                    ? d.name + ' <span class="text-muted small ms-1">' + d.unit + '</span>'
                    : d.name;

                document.getElementById('tbody-' + section).insertAdjacentHTML('beforeend',
                    '<tr id="item-' + d.id + '">' +
                    '<td><span class="fw-medium">' + nameHtml + '</span></td>' +
                    '<td class="text-end">' + d.qty + '</td>' +
                    '<td class="text-end">' + parseFloat(d.unit_price).toFixed(2) + '</td>' +
                    '<td class="text-end fw-medium">' + parseFloat(d.total).toFixed(2) + '</td>' +
                    '<td class="text-end">' +
                        '<button type="button" class="btn btn-xs btn-outline-primary border-0 p-0 px-1 me-1"' +
                            ' data-bs-toggle="modal" data-bs-target="#editItemModal"' +
                            ' data-item-id="' + d.id + '" data-item-name="' + d.name + '"' +
                            ' data-item-qty="' + d.qty + '" data-item-price="' + d.unit_price + '"' +
                            ' data-item-url="' + d.update_url + '"><i class="bi bi-pencil"></i></button>' +
                        '<form method="POST" action="' + d.destroy_url + '" class="d-inline"' +
                            ' onsubmit="return confirm(\'' + CONFIRM_MSG + '\')">' +
                            '<input type="hidden" name="_token" value="' + CSRF + '">' +
                            '<input type="hidden" name="_method" value="DELETE">' +
                            '<button class="btn btn-xs btn-outline-danger border-0 p-0 px-1"><i class="bi bi-x-lg"></i></button>' +
                        '</form>' +
                    '</td></tr>'
                );

                // Update section subtotal
                const subtotalEl = document.getElementById('subtotal-' + section);
                if (subtotalEl) {
                    const prev = parseFloat(subtotalEl.textContent.replace(/,/g, '')) || 0;
                    subtotalEl.textContent = (prev + parseFloat(d.total)).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
                }

                // Update badge count
                const badge = document.getElementById('badge-' + section);
                if (badge) {
                    badge.textContent = (parseInt(badge.textContent) || 0) + 1;
                    badge.classList.remove('d-none');
                }

                // Update grand total
                const gt = document.getElementById('grand-total-display');
                if (gt) gt.textContent = parseFloat(data.invoice_total).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});

                // Reset add row
                select.value     = '';
                qtyEl.value      = 1;
                priceEl.value    = '';
                priceEl.readOnly = true;
                document.getElementById('preview-' + section).textContent = '—';

            } catch (e) {
                alert('Error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-plus-lg"></i>';
            }
        });
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
