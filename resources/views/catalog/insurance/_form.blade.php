<div class="mb-3">
    <label class="form-label" for="name">Company Name <span class="text-danger">*</span></label>
    <input
        id="name"
        type="text"
        name="name"
        value="{{ old('name', $insuranceCompany->name ?? '') }}"
        class="form-control @error('name') is-invalid @enderror"
        required
        autofocus
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="contact_info">Contact Info</label>
    <textarea
        id="contact_info"
        name="contact_info"
        rows="4"
        class="form-control @error('contact_info') is-invalid @enderror"
        placeholder="Phone, email, address…"
    >{{ old('contact_info', $insuranceCompany->contact_info ?? '') }}</textarea>
    @error('contact_info')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
