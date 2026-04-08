<div class="mb-3">
    <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
    <input id="name" type="text" name="name"
           value="{{ old('name', $medication->name ?? '') }}"
           class="form-control @error('name') is-invalid @enderror"
           required autofocus>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="unit">{{ __('Unit') }} <span class="text-danger">*</span></label>
    <input id="unit" type="text" name="unit"
           value="{{ old('unit', $medication->unit ?? '') }}"
           class="form-control @error('unit') is-invalid @enderror"
           placeholder="مثال: حبة، قارورة، مغ"
           required>
    @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
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

<div class="mb-3">
    <label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
    <div class="d-flex gap-4">
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
