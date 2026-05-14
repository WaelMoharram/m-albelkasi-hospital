<div class="row g-3 mb-3">
    <div class="col-md-5">
        <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $medication->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="code">{{ __('Item Code') }}</label>
        <input id="code" type="text" name="code"
               value="{{ old('code', $medication->code ?? '') }}"
               class="form-control @error('code') is-invalid @enderror"
               placeholder="{{ __('Optional') }}">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="unit">{{ __('Unit') }}</label>
        <select id="unit" name="unit"
                class="form-select @error('unit') is-invalid @enderror">
            <option value="">— {{ __('Optional') }} —</option>
            @foreach ($units as $u)
                <option value="{{ $u->name }}"
                    {{ old('unit', $medication->unit ?? '') === $u->name ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>
        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">
            <a href="{{ route('catalog.units.index') }}" target="_blank" class="small">
                <i class="bi bi-plus-circle"></i> {{ __('Manage Units') }}
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label" for="price">{{ __('Price') }} <span class="text-danger">*</span></label>
        <div class="input-group">
            <input id="price" type="number" name="price"
                   value="{{ old('price', $medication->price ?? '') }}"
                   class="form-control @error('price') is-invalid @enderror"
                   step="0.01" min="0" required>
            <span class="input-group-text">ج.م</span>
            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="daily_qty">{{ __('Times per day') }}</label>
        <div class="input-group">
            <input id="daily_qty" type="number" name="daily_qty"
                   value="{{ old('daily_qty', $medication->daily_qty ?? 1) }}"
                   class="form-control text-center @error('daily_qty') is-invalid @enderror"
                   min="1" max="99" step="1">
            <span class="input-group-text">×</span>
            @error('daily_qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-text">{{ __('Times/day charged on invoice.') }}</div>
    </div>
    <div class="col-md-5 d-flex align-items-start pt-4">
        <div>
            <label class="form-label d-block">{{ __('Type') }} <span class="text-danger">*</span></label>
            <div class="d-flex gap-4 mt-1">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" id="type_local" value="local"
                           {{ old('type', $medication->type ?? '') === 'local' ? 'checked' : '' }} required>
                    <label class="form-check-label" for="type_local">{{ __('Local') }}</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" id="type_imported" value="imported"
                           {{ old('type', $medication->type ?? '') === 'imported' ? 'checked' : '' }}>
                    <label class="form-check-label" for="type_imported">{{ __('Imported') }}</label>
                </div>
            </div>
            @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

{{-- Linked services --}}
@if(! empty($allServices) && $allServices->count())
@php
    $selectedTriggers = old('triggers',
        isset($medication) ? $medication->triggeredServices->pluck('id')->map(fn($id) => (string)$id)->all() : []
    );
@endphp

<div class="mb-3">
    <label class="form-label fw-semibold" for="med_triggers">
        <i class="bi bi-link-45deg ms-1"></i>
        {{ __('Linked Services') }}
    </label>
    <div class="form-text mb-2">{{ __('These services will be added automatically when this medication is added to an invoice (e.g. IV set with IV solution).') }}</div>

    <select id="med_triggers" name="triggers[]" multiple>
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
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
new TomSelect('#med_triggers', {
    plugins: ['remove_button', 'clear_button'],
    placeholder: '— {{ __("Select linked services") }} —',
    maxOptions: null,
    closeAfterSelect: false,
    hideSelected: false,
});
</script>
@endpush
@endif
