<div class="mb-3">
    <label class="form-label" for="name">{{ __('Category Name') }} <span class="text-danger">*</span></label>
    <input id="name" type="text" name="name"
           value="{{ old('name', $category->name ?? '') }}"
           class="form-control @error('name') is-invalid @enderror"
           required autofocus>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">{{ __('This name appears as a section header in the invoice printout.') }}</div>
</div>

<div class="mb-3">
    <label class="form-label" for="sort_order">{{ __('Sort Order') }} <span class="text-danger">*</span></label>
    <input id="sort_order" type="number" name="sort_order"
           value="{{ old('sort_order', $category->sort_order ?? 99) }}"
           class="form-control @error('sort_order') is-invalid @enderror"
           min="0" required style="max-width:120px;">
    @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">{{ __('Lower numbers appear first in the invoice.') }}</div>
</div>
