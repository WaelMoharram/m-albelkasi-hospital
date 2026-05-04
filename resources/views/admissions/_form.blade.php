<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="patient_id">{{ __('Patient') }} <span class="text-danger">*</span></label>
        <select id="patient_id" name="patient_id"
                class="@error('patient_id') is-invalid @enderror" required>
            <option value="">— {{ __('Search by name or national ID…') }} —</option>
            @foreach ($patients as $p)
                <option value="{{ $p->id }}"
                    {{ old('patient_id', $admission->patient_id ?? $selectedPatient ?? '') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }} — {{ $p->national_id }}
                </option>
            @endforeach
        </select>
        @error('patient_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
    .ts-wrapper .ts-control          { direction: rtl; text-align: right; }
    .ts-dropdown                     { direction: rtl; text-align: right; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
new TomSelect('#patient_id', {
    placeholder: '— {{ __("Search by name or national ID…") }} —',
    maxOptions: null,
    highlight: true,
});
</script>
@endpush

    <div class="col-md-4">
        <label class="form-label" for="admission_date">{{ __('Admission Date') }} <span class="text-danger">*</span></label>
        <input id="admission_date" type="date" name="admission_date"
               value="{{ old('admission_date', isset($admission) ? $admission->admission_date->toDateString() : now()->toDateString()) }}"
               max="{{ now()->toDateString() }}"
               class="form-control @error('admission_date') is-invalid @enderror" required>
        @error('admission_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="room">{{ __('Room') }}</label>
        <input id="room" type="text" name="room"
               value="{{ old('room', $admission->room ?? '') }}"
               class="form-control @error('room') is-invalid @enderror"
               placeholder="مثال: 204">
        @error('room') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="ward">{{ __('Ward') }}</label>
        <input id="ward" type="text" name="ward"
               value="{{ old('ward', $admission->ward ?? '') }}"
               class="form-control @error('ward') is-invalid @enderror"
               placeholder="مثال: القلب">
        @error('ward') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="referral_number">{{ __('Referral Number') }}</label>
        <input id="referral_number" type="text" name="referral_number"
               value="{{ old('referral_number', $admission->referral_number ?? '') }}"
               class="form-control @error('referral_number') is-invalid @enderror"
               placeholder="{{ __('Optional') }}">
        @error('referral_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="referral_source">{{ __('Referral Source') }}</label>
        <select id="referral_source" name="referral_source"
                class="form-select @error('referral_source') is-invalid @enderror">
            <option value="">— {{ __('Optional') }} —</option>
            @foreach ($insuranceCompanies as $company)
                <option value="{{ $company }}"
                    {{ old('referral_source', $admission->referral_source ?? '') === $company ? 'selected' : '' }}>
                    {{ $company }}
                </option>
            @endforeach
        </select>
        @error('referral_source') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
