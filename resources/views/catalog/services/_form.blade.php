<div class="mb-3">
    <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
    <input
        id="name"
        type="text"
        name="name"
        value="{{ old('name', $service->name ?? '') }}"
        class="form-control @error('name') is-invalid @enderror"
        required
        autofocus
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="price">Price <span class="text-danger">*</span></label>
    <div class="input-group">
        <span class="input-group-text">$</span>
        <input
            id="price"
            type="number"
            name="price"
            value="{{ old('price', $service->price ?? '') }}"
            class="form-control @error('price') is-invalid @enderror"
            step="0.01"
            min="0"
            required
        >
        @error('price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="category">Category <span class="text-danger">*</span></label>
    <select
        id="category"
        name="category"
        class="form-select @error('category') is-invalid @enderror"
        required
    >
        <option value="">— Select —</option>
        @foreach (['daily' => 'Daily', 'lab' => 'Lab', 'radiology' => 'Radiology'] as $val => $label)
            <option value="{{ $val }}"
                {{ old('category', $service->category ?? '') === $val ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('category')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
