<div class="mb-3">
    <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
    <input id="name" type="text" name="name"
           value="{{ old('name', $service->name ?? '') }}"
           class="form-control @error('name') is-invalid @enderror"
           required autofocus>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

<div class="mb-3">
    <label class="form-label" for="category">{{ __('Category') }} <span class="text-danger">*</span></label>
    <select id="category" name="category"
            class="form-select @error('category') is-invalid @enderror" required>
        <option value="">— {{ __('Select —') }}</option>
        @foreach ([
            'daily'     => __('Daily'),
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
</div>
