<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $patient->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="national_id">National ID <span class="text-danger">*</span></label>
        <input id="national_id" type="text" name="national_id"
               value="{{ old('national_id', $patient->national_id ?? '') }}"
               class="form-control @error('national_id') is-invalid @enderror"
               required>
        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="dob">Date of Birth <span class="text-danger">*</span></label>
        <input id="dob" type="date" name="dob"
               value="{{ old('dob', isset($patient) ? $patient->dob->toDateString() : '') }}"
               max="{{ now()->subDay()->toDateString() }}"
               class="form-control @error('dob') is-invalid @enderror"
               required>
        @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Gender <span class="text-danger">*</span></label>
        <div class="d-flex gap-4 mt-1">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male"
                       {{ old('gender', $patient->gender ?? '') === 'male' ? 'checked' : '' }} required>
                <label class="form-check-label" for="gender_male">Male</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female"
                       {{ old('gender', $patient->gender ?? '') === 'female' ? 'checked' : '' }}>
                <label class="form-check-label" for="gender_female">Female</label>
            </div>
        </div>
        @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        {{-- spacer --}}
    </div>

    <div class="col-md-8">
        <label class="form-label" for="insurance_company_id">Insurance Company <span class="text-danger">*</span></label>
        <select id="insurance_company_id" name="insurance_company_id"
                class="form-select @error('insurance_company_id') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach ($insuranceCompanies as $id => $name)
                <option value="{{ $id }}"
                    {{ old('insurance_company_id', $patient->insurance_company_id ?? '') == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
        @error('insurance_company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="policy_number">Policy Number <span class="text-danger">*</span></label>
        <input id="policy_number" type="text" name="policy_number"
               value="{{ old('policy_number', $patient->policy_number ?? '') }}"
               class="form-control @error('policy_number') is-invalid @enderror"
               required>
        @error('policy_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
