<div class="row g-3">

    {{-- Name --}}
    <div class="col-md-6">
        <label class="form-label" for="name">{{ __('Full Name') }} <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name"
               value="{{ old('name', $patient->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- National ID — auto-extracts DOB, gender, governorate --}}
    <div class="col-md-6">
        <label class="form-label" for="national_id">
            {{ __('National ID') }} <span class="text-danger">*</span>
            <span id="nid-spinner" class="ms-1 d-none">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
            </span>
        </label>
        <input id="national_id" type="text" name="national_id"
               value="{{ old('national_id', $patient->national_id ?? '') }}"
               class="form-control font-monospace @error('national_id') is-invalid @enderror"
               maxlength="14"
               inputmode="numeric"
               pattern="\d{14}"
               placeholder="{{ __('14-digit Egyptian National ID') }}"
               required>
        @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

        {{-- Extracted governorate pill --}}
        <div id="nid-gov-wrap" class="mt-1 d-none">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                <i class="bi bi-geo-alt-fill ms-1"></i>
                <span id="nid-gov-label"></span>
            </span>
            <span class="text-muted small me-1">{{ __('extracted from ID') }}</span>
        </div>

        {{-- Validation hint --}}
        <div id="nid-invalid-hint" class="text-danger small mt-1 d-none">
            <i class="bi bi-exclamation-circle ms-1"></i>{{ __('Invalid ID format.') }}
        </div>
    </div>

    {{-- DOB --}}
    <div class="col-md-4">
        <label class="form-label" for="dob">
            {{ __('Date of Birth') }} <span class="text-danger">*</span>
            <span id="dob-auto-badge" class="badge bg-success-subtle text-success border border-success-subtle ms-1 d-none">تلقائي</span>
        </label>
        <input id="dob" type="date" name="dob"
               value="{{ old('dob', isset($patient) ? $patient->dob->toDateString() : '') }}"
               max="{{ now()->subDay()->toDateString() }}"
               class="form-control @error('dob') is-invalid @enderror"
               required>
        @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Gender --}}
    <div class="col-md-4">
        <label class="form-label">
            {{ __('Gender') }} <span class="text-danger">*</span>
            <span id="gender-auto-badge" class="badge bg-success-subtle text-success border border-success-subtle ms-1 d-none">تلقائي</span>
        </label>
        <div class="d-flex gap-4 mt-1" id="gender-radio-group">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male"
                       {{ old('gender', $patient->gender ?? '') === 'male' ? 'checked' : '' }} required>
                <label class="form-check-label" for="gender_male">{{ __('Male') }}</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female"
                       {{ old('gender', $patient->gender ?? '') === 'female' ? 'checked' : '' }}>
                <label class="form-check-label" for="gender_female">{{ __('Female') }}</label>
            </div>
        </div>
        @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Governorate (read-only text, populated by JS) --}}
    <div class="col-md-4">
        <label class="form-label text-muted">{{ __('Governorate') }}</label>
        <input id="governorate_text" type="text"
               class="form-control bg-light text-muted"
               placeholder="{{ __('Auto-filled from National ID') }}"
               readonly tabindex="-1">
    </div>

    {{-- Insurance company --}}
    <div class="col-md-8">
        <label class="form-label" for="insurance_company_id">{{ __('Insurance Company') }} <span class="text-danger">*</span></label>
        <select id="insurance_company_id" name="insurance_company_id"
                class="form-select @error('insurance_company_id') is-invalid @enderror" required>
            <option value="">— {{ __('Select —') }}</option>
            @foreach ($insuranceCompanies as $id => $insName)
                <option value="{{ $id }}"
                    {{ old('insurance_company_id', $patient->insurance_company_id ?? '') == $id ? 'selected' : '' }}>
                    {{ $insName }}
                </option>
            @endforeach
        </select>
        @error('insurance_company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>


</div>

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Governorate lookup table (Arabic names) ──────────────────────────────
    const GOV_CODES = {
        '01': 'القاهرة',      '02': 'الإسكندرية',   '03': 'بورسعيد',
        '04': 'السويس',       '11': 'دمياط',         '12': 'الدقهلية',
        '13': 'الشرقية',      '14': 'القليوبية',     '15': 'كفر الشيخ',
        '16': 'الغربية',      '17': 'المنوفية',      '18': 'البحيرة',
        '19': 'الإسماعيلية',  '21': 'الجيزة',        '22': 'بني سويف',
        '23': 'الفيوم',       '24': 'المنيا',        '25': 'أسيوط',
        '26': 'سوهاج',        '27': 'قنا',           '28': 'أسوان',
        '29': 'الأقصر',       '31': 'البحر الأحمر',  '32': 'الوادي الجديد',
        '33': 'مطروح',        '34': 'شمال سيناء',    '35': 'جنوب سيناء',
        '88': 'أجنبي',
    };

    // ── Element refs ─────────────────────────────────────────────────────────
    const nidInput        = document.getElementById('national_id');
    const dobInput        = document.getElementById('dob');
    const govTextInput    = document.getElementById('governorate_text');
    const govWrap         = document.getElementById('nid-gov-wrap');
    const govLabel        = document.getElementById('nid-gov-label');
    const invalidHint     = document.getElementById('nid-invalid-hint');
    const dobAutoBadge    = document.getElementById('dob-auto-badge');
    const genderAutoBadge = document.getElementById('gender-auto-badge');

    if (!nidInput) return;

    // ── Helpers ───────────────────────────────────────────────────────────────
    function flashFilled(el) {
        el.classList.add('nid-autofill-flash');
        setTimeout(() => el.classList.remove('nid-autofill-flash'), 1000);
    }

    function showBadge(el) { el.classList.remove('d-none'); }
    function hideBadge(el) { el.classList.add('d-none'); }

    function clearAutoFields() {
        dobInput.value = '';
        document.querySelectorAll('input[name="gender"]').forEach(r => r.checked = false);
        govTextInput.value = '';
        govWrap.classList.add('d-none');
        govLabel.textContent = '';
        invalidHint.classList.add('d-none');
        hideBadge(dobAutoBadge);
        hideBadge(genderAutoBadge);
        nidInput.classList.remove('is-valid', 'border-danger');
    }

    function showError() {
        invalidHint.classList.remove('d-none');
        nidInput.classList.add('border-danger');
        nidInput.classList.remove('is-valid');
    }

    function clearError() {
        invalidHint.classList.add('d-none');
        nidInput.classList.remove('border-danger');
    }

    // ── Main parser ───────────────────────────────────────────────────────────
    function parseNationalId(raw) {
        const digits = raw.replace(/\D/g, '');

        if (digits.length < 14) {
            clearAutoFields();
            clearError();
            return;
        }

        const centuryCode = digits[0];
        let century;
        if      (centuryCode === '2') century = '19';
        else if (centuryCode === '3') century = '20';
        else { clearAutoFields(); showError(); return; }

        const year  = century + digits.substring(1, 3);
        const month = digits.substring(3, 5);
        const day   = digits.substring(5, 7);

        const parsedDate = new Date(`${year}-${month}-${day}`);
        const isValidDate =
            !isNaN(parsedDate.getTime()) &&
            parsedDate.getFullYear() === parseInt(year, 10) &&
            parsedDate.getMonth()    === parseInt(month, 10) - 1 &&
            parsedDate.getDate()     === parseInt(day, 10)   &&
            parsedDate < new Date();

        if (!isValidDate) { clearAutoFields(); showError(); return; }

        const govCode = digits.substring(7, 9);
        const govName = GOV_CODES[govCode] ?? `غير معروف (${govCode})`;
        const gender  = parseInt(digits[12], 10) % 2 !== 0 ? 'male' : 'female';

        clearError();

        const dobValue = `${year}-${month}-${day}`;
        if (dobInput.value !== dobValue) {
            dobInput.value = dobValue;
            flashFilled(dobInput);
        }
        showBadge(dobAutoBadge);

        const genderRadio = document.getElementById(`gender_${gender}`);
        if (genderRadio && !genderRadio.checked) {
            genderRadio.checked = true;
            flashFilled(document.getElementById('gender-radio-group'));
        }
        showBadge(genderAutoBadge);

        govTextInput.value   = govName;
        govLabel.textContent = govName;
        govWrap.classList.remove('d-none');

        nidInput.classList.add('is-valid');
    }

    nidInput.addEventListener('input', function () {
        parseNationalId(this.value);
    });

    if (nidInput.value.trim().length === 14) {
        parseNationalId(nidInput.value.trim());
    }

}());
</script>

<style>
    @keyframes nidFillPulse {
        0%   { box-shadow: 0 0 0 0   rgba(25, 135,  84, 0.55); background-color: rgba(25, 135,  84, 0.08); }
        60%  { box-shadow: 0 0 0 5px rgba(25, 135,  84, 0);    background-color: rgba(25, 135,  84, 0.04); }
        100% { box-shadow: none; background-color: transparent; }
    }

    .nid-autofill-flash {
        animation: nidFillPulse 0.9s ease-out forwards;
        border-radius: 0.375rem;
    }

    #national_id.is-valid {
        border-color: #198754;
        background-image: none;
        padding-left: 0.75rem;
    }

    #governorate_text {
        cursor: default;
    }
</style>
@endpush
