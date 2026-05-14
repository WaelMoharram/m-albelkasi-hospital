<div class="row g-3 mb-3">
    <div class="col-md-5">
        <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $service->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="code">{{ __('Item Code') }}</label>
        <input id="code" type="text" name="code"
               value="{{ old('code', $service->code ?? '') }}"
               class="form-control @error('code') is-invalid @enderror"
               placeholder="{{ __('Optional') }}">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="price">{{ __('Price') }} <span class="text-danger">*</span></label>
        <div class="input-group">
            <input id="price" type="number" name="price"
                   value="{{ old('price', $service->price ?? '') }}"
                   class="form-control @error('price') is-invalid @enderror"
                   step="0.01" min="0" required>
            <span class="input-group-text">ج.م</span>
            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label" for="category">{{ __('Category') }} <span class="text-danger">*</span></label>
        <select id="category" name="category"
                class="form-select @error('category') is-invalid @enderror" required>
            <option value="">— {{ __('Select —') }}</option>
            @foreach ([
                'supplies'  => __('Supplies'),
                'lab'       => __('Lab'),
                'radiology' => __('Radiology'),
                'other'     => __('Other'),
            ] as $val => $lbl)
                <option value="{{ $val }}"
                    {{ old('category', $service->category ?? '') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
            @endforeach
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-8">
        <label class="form-label" for="invoice_category_id">{{ __('Invoice Section') }}</label>
        <select id="invoice_category_id" name="invoice_category_id"
                class="form-select @error('invoice_category_id') is-invalid @enderror">
            <option value="">— {{ __('None (appears in section summary only)') }}</option>
            @foreach($invoiceCategories ?? [] as $cat)
                <option value="{{ $cat->id }}"
                    {{ old('invoice_category_id', $service->invoice_category_id ?? '') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->sort_order }}. {{ $cat->name }}
                </option>
            @endforeach
        </select>
        @error('invoice_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Auto-charge toggles --}}
<div class="card border-0 bg-light rounded mb-3 p-3">
    <div class="fw-semibold small text-muted mb-2">{{ __('Auto-add to Invoice') }}</div>

    <div class="d-flex align-items-start gap-3 mb-2">
        <div class="form-check form-switch flex-grow-1 mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   id="is_daily" name="is_daily" value="1"
                   {{ old('is_daily', $service->is_daily ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_daily">
                <span class="fw-semibold">{{ __('Auto-charge daily') }}</span>
                <span class="text-muted small d-block">{{ __('If enabled, this service is added automatically for every day of admission.') }}</span>
            </label>
        </div>
        <div id="daily-qty-wrap" style="{{ old('is_daily', $service->is_daily ?? false) ? '' : 'display:none;' }} min-width:130px;">
            <label class="form-label small text-muted mb-1" for="daily_qty">{{ __('Times per day') }}</label>
            <div class="input-group input-group-sm" style="width:120px;">
                <input id="daily_qty" type="number" name="daily_qty"
                       value="{{ old('daily_qty', $service->daily_qty ?? 1) }}"
                       class="form-control text-center"
                       min="1" max="99" step="1">
                <span class="input-group-text">×</span>
            </div>
        </div>
    </div>

    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch"
               id="is_once" name="is_once" value="1"
               {{ old('is_once', $service->is_once ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_once">
            <span class="fw-semibold">{{ __('Add once at admission') }}</span>
            <span class="text-muted small d-block">{{ __('If enabled, this service is added once automatically when the admission is created.') }}</span>
        </label>
    </div>
</div>

{{-- Linked (triggered) services --}}
@if(! empty($allServices) && $allServices->count())
@php
    $selectedTriggers = old('triggers',
        isset($service) ? $service->triggers->pluck('id')->map(fn($id) => (string)$id)->all() : []
    );
@endphp

<div class="mb-3">
    <label class="form-label fw-semibold" for="triggers">
        <i class="bi bi-link-45deg ms-1"></i>
        {{ __('Linked Services') }}
    </label>
    <div class="form-text mb-2">{{ __('These services will be added automatically when this service is added to an invoice.') }}</div>

    <select id="triggers" name="triggers[]" multiple>
        @foreach($allServices->groupBy('category') as $cat => $group)
            <optgroup label="{{ match($cat) { 'supplies' => __('Supplies'), 'lab' => __('Lab'), 'radiology' => __('Radiology'), 'other' => __('Other'), default => $cat } }}">
                @foreach($group as $s)
                    <option value="{{ $s->id }}"
                        {{ in_array((string)$s->id, $selectedTriggers) ? 'selected' : '' }}>
                        {{ $s->name }} — {{ number_format($s->price, 2) }} ج.م
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
    .ts-wrapper .ts-control          { direction: rtl; text-align: right; min-height: 38px; }
    .ts-dropdown                     { direction: rtl; text-align: right; }
    .ts-dropdown .optgroup-header    { font-size: .75rem; text-transform: none; color: #6c757d; font-weight: 600; }
    .ts-wrapper.multi .ts-control > .item { background: #e9ecef; border-radius: .25rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var toggle = document.getElementById('is_daily');
    var wrap   = document.getElementById('daily-qty-wrap');
    var qty    = document.getElementById('daily_qty');
    if (toggle && wrap) {
        toggle.addEventListener('change', function () {
            wrap.style.display = this.checked ? '' : 'none';
            if (!this.checked) qty.value = 1;
        });
    }
}());
</script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
new TomSelect('#triggers', {
    plugins: ['remove_button', 'clear_button'],
    placeholder: '— {{ __("Select linked services") }} —',
    maxOptions: null,
    closeAfterSelect: false,
    hideSelected: false,
});
</script>
@endpush
@endif
