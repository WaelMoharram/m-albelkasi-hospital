<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $medication->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="code">{{ __('Item Code') }}</label>
        <input id="code" type="text" name="code"
               value="{{ old('code', $medication->code ?? '') }}"
               class="form-control @error('code') is-invalid @enderror"
               placeholder="{{ __('Optional') }}">
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
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
