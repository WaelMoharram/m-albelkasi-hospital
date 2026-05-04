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
        <label class="form-label" for="ward">{{ __('Ward') }}</label>
        @if($wards->isNotEmpty())
        <select id="ward" name="ward"
                class="form-select @error('ward') is-invalid @enderror">
            <option value="">— {{ __('Select —') }}</option>
            @foreach ($wards as $w)
                <option value="{{ $w->name }}"
                    {{ old('ward', $admission->ward ?? '') === $w->name ? 'selected' : '' }}
                    data-rooms="{{ $w->rooms->pluck('name')->toJson() }}">
                    {{ $w->name }}
                </option>
            @endforeach
        </select>
        @else
        <input id="ward" type="text" name="ward"
               value="{{ old('ward', $admission->ward ?? '') }}"
               class="form-control @error('ward') is-invalid @enderror"
               placeholder="{{ __('Ward name') }}">
        @endif
        @error('ward') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="room">{{ __('Room') }}</label>
        <select id="room" name="room"
                class="form-select @error('room') is-invalid @enderror">
            <option value="">— {{ __('Select ward first') }} —</option>
            {{-- Options populated by JS based on ward selection --}}
            @if(old('room', $admission->room ?? ''))
                <option value="{{ old('room', $admission->room ?? '') }}" selected>
                    {{ old('room', $admission->room ?? '') }}
                </option>
            @endif
        </select>
        @error('room') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

@push('scripts')
<script>
(function () {
    var wardSel = document.getElementById('ward');
    var roomSel = document.getElementById('room');
    if (!wardSel || !roomSel) return;

    var currentRoom = '{{ old('room', $admission->room ?? '') }}';

    function populateRooms(rooms, selected) {
        roomSel.innerHTML = '<option value="">— {{ __("Select —") }} —</option>';
        rooms.forEach(function (r) {
            var opt = document.createElement('option');
            opt.value = r;
            opt.textContent = r;
            if (r === selected) opt.selected = true;
            roomSel.appendChild(opt);
        });
        if (rooms.length === 0) {
            roomSel.innerHTML = '<option value="">— {{ __("No rooms") }} —</option>';
        }
    }

    wardSel.addEventListener('change', function () {
        var opt   = this.options[this.selectedIndex];
        var rooms = opt.dataset.rooms ? JSON.parse(opt.dataset.rooms) : [];
        populateRooms(rooms, '');
    });

    // On load: restore rooms for the already-selected ward
    var selectedOpt = wardSel.options[wardSel.selectedIndex];
    if (selectedOpt && selectedOpt.dataset.rooms) {
        populateRooms(JSON.parse(selectedOpt.dataset.rooms), currentRoom);
    }
}());
</script>
@endpush

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
