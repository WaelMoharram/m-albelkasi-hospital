<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="patient_id">{{ __('Patient') }} <span class="text-danger">*</span></label>
        <select id="patient_id" name="patient_id"
                class="form-select @error('patient_id') is-invalid @enderror" required>
            <option value="">— {{ __('Select —') }}</option>
            @foreach ($patients as $p)
                <option value="{{ $p->id }}"
                    {{ old('patient_id', $admission->patient_id ?? $selectedPatient ?? '') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }} ({{ $p->national_id }})
                </option>
            @endforeach
        </select>
        @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

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
</div>
