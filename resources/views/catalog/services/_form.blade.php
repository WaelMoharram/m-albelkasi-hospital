<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $service->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="code">{{ __('Item Code') }}</label>
        <input id="code" type="text" name="code"
               value="{{ old('code', $service->code ?? '') }}"
               class="form-control @error('code') is-invalid @enderror"
               placeholder="{{ __('Optional') }}">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
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

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label" for="category">{{ __('Category') }} <span class="text-danger">*</span></label>
        <select id="category" name="category"
                class="form-select @error('category') is-invalid @enderror" required>
            <option value="">— {{ __('Select —') }}</option>
            @foreach ([
                'supplies'  => __('Supplies'),
                'lab'       => __('Lab'),
                'radiology' => __('Radiology'),
            ] as $val => $lbl)
                <option value="{{ $val }}"
                    {{ old('category', $service->category ?? '') === $val ? 'selected' : '' }}>
                    {{ $lbl }}
                </option>
            @endforeach
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">{{ __('Which section this service appears in on the printed invoice.') }}</div>
    </div>

    <div class="col-md-6">
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
        <div class="form-text">{{ __('Which section this service appears in on the printed invoice.') }}</div>
    </div>
</div>

{{-- Auto-charge daily toggle --}}
<div class="mb-3">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch"
               id="is_daily" name="is_daily" value="1"
               {{ old('is_daily', $service->is_daily ?? false) ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="is_daily">
            {{ __('Auto-charge daily') }}
        </label>
    </div>
    <div class="form-text">{{ __('If enabled, this service is added automatically for every day of admission.') }}</div>
</div>

{{-- Linked (triggered) services --}}
@if(! empty($allServices) && $allServices->count())
<div class="mb-3">
    <label class="form-label fw-semibold">
        <i class="bi bi-link-45deg ms-1"></i>
        {{ __('Linked Services') }}
    </label>
    <div class="form-text mb-2">{{ __('These services will be added automatically when this service is added to an invoice.') }}</div>

    @php
        $selectedTriggers = old('triggers',
            isset($service) ? $service->triggers->pluck('id')->map(fn($id) => (string)$id)->all() : []
        );
    @endphp

    @foreach($allServices->groupBy('category') as $cat => $group)
        <div class="mb-2">
            <span class="badge bg-secondary mb-1">
                {{ match($cat) { 'daily' => __('Daily'), 'supplies' => __('Supplies'), 'lab' => __('Lab'), 'radiology' => __('Radiology'), default => $cat } }}
            </span>
            @foreach($group as $s)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="triggers[]"
                           id="trigger_{{ $s->id }}"
                           value="{{ $s->id }}"
                           {{ in_array((string)$s->id, $selectedTriggers) ? 'checked' : '' }}>
                    <label class="form-check-label" for="trigger_{{ $s->id }}">
                        {{ $s->name }}
                        <span class="text-muted small">({{ number_format($s->price, 2) }} ج.م)</span>
                    </label>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endif
