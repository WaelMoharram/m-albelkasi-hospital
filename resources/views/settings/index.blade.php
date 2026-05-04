@extends('layouts.app')

@section('title', __('Settings'))
@section('page_title', __('Settings'))

@section('breadcrumb')
    <li class="breadcrumb-item active">{{ __('Settings') }}</li>
@endsection

@section('content')
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

<div class="row g-4">

    {{-- Hospital Info --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-hospital ms-2 text-primary"></i>
                    {{ __('Hospital Information') }}
                </h6>
            </div>
            <div class="card-body p-4">

                <div class="mb-3">
                    <label class="form-label" for="hospital_name">{{ __('Hospital Name') }} <span class="text-danger">*</span></label>
                    <input id="hospital_name" type="text" name="hospital_name"
                           value="{{ old('hospital_name', $settings['hospital_name']) }}"
                           class="form-control @error('hospital_name') is-invalid @enderror" required>
                    @error('hospital_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="hospital_po_box">{{ __('P.O. Box') }}</label>
                        <input id="hospital_po_box" type="text" name="hospital_po_box"
                               value="{{ old('hospital_po_box', $settings['hospital_po_box']) }}"
                               class="form-control @error('hospital_po_box') is-invalid @enderror">
                        @error('hospital_po_box') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="hospital_commercial_reg">{{ __('Commercial Register') }}</label>
                        <input id="hospital_commercial_reg" type="text" name="hospital_commercial_reg"
                               value="{{ old('hospital_commercial_reg', $settings['hospital_commercial_reg']) }}"
                               class="form-control @error('hospital_commercial_reg') is-invalid @enderror">
                        @error('hospital_commercial_reg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="hospital_address">{{ __('Address') }}</label>
                    <textarea id="hospital_address" name="hospital_address" rows="2"
                              class="form-control @error('hospital_address') is-invalid @enderror">{{ old('hospital_address', $settings['hospital_address']) }}</textarea>
                    @error('hospital_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="hospital_phones">{{ __('Phone Numbers') }}</label>
                    <input id="hospital_phones" type="text" name="hospital_phones"
                           value="{{ old('hospital_phones', $settings['hospital_phones']) }}"
                           placeholder="{{ __('e.g.') }} 047/2560590 - 0127414744"
                           class="form-control @error('hospital_phones') is-invalid @enderror">
                    @error('hospital_phones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>
        </div>

        {{-- Invoice Layout --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-receipt ms-2 text-success"></i>
                    {{ __('Invoice Layout') }}
                </h6>
            </div>
            <div class="card-body p-4">

                <div class="mb-3">
                    <label class="form-label" for="invoice_footer_note">{{ __('Footer Note') }}</label>
                    <textarea id="invoice_footer_note" name="invoice_footer_note" rows="2"
                              class="form-control @error('invoice_footer_note') is-invalid @enderror">{{ old('invoice_footer_note', $settings['invoice_footer_note']) }}</textarea>
                    @error('invoice_footer_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">{{ __('Appears at the bottom of each printed invoice.') }}</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="invoice_prepared_by">{{ __('Prepared By Label') }}</label>
                        <input id="invoice_prepared_by" type="text" name="invoice_prepared_by"
                               value="{{ old('invoice_prepared_by', $settings['invoice_prepared_by']) }}"
                               class="form-control @error('invoice_prepared_by') is-invalid @enderror">
                        @error('invoice_prepared_by') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="invoice_approved_by">{{ __('Approved By Label') }}</label>
                        <input id="invoice_approved_by" type="text" name="invoice_approved_by"
                               value="{{ old('invoice_approved_by', $settings['invoice_approved_by']) }}"
                               class="form-control @error('invoice_approved_by') is-invalid @enderror">
                        @error('invoice_approved_by') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

            </div>
        </div>

        {{-- Medication Discounts --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-percent ms-2 text-warning"></i>
                    {{ __('Medication Discounts') }}
                </h6>
            </div>
            <div class="card-body p-4">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="local_med_discount">{{ __('Local Medications Discount') }}</label>
                        <div class="input-group">
                            <input id="local_med_discount" type="number" name="local_med_discount"
                                   value="{{ old('local_med_discount', $settings['local_med_discount']) }}"
                                   class="form-control @error('local_med_discount') is-invalid @enderror"
                                   step="0.01" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                            @error('local_med_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">{{ __('Applied to the local medications subtotal on every invoice.') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="imported_med_discount">{{ __('Imported Medications Discount') }}</label>
                        <div class="input-group">
                            <input id="imported_med_discount" type="number" name="imported_med_discount"
                                   value="{{ old('imported_med_discount', $settings['imported_med_discount']) }}"
                                   class="form-control @error('imported_med_discount') is-invalid @enderror"
                                   step="0.01" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                            @error('imported_med_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">{{ __('Applied to the imported medications subtotal on every invoice.') }}</div>
                    </div>
                </div>

                <hr class="mt-4">

                <div class="mt-3">
                    <label class="form-label" for="icu_beds">{{ __('ICU Beds Count') }}</label>
                    <input id="icu_beds" type="number" name="icu_beds"
                           value="{{ old('icu_beds', $settings['icu_beds']) }}"
                           class="form-control @error('icu_beds') is-invalid @enderror"
                           min="1" max="1000" style="max-width:140px;">
                    <div class="form-text">{{ __('Used in performance indicators report.') }}</div>
                    @error('icu_beds') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Logo --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-image ms-2 text-info"></i>
                    {{ __('Hospital Logo') }}
                </h6>
            </div>
            <div class="card-body p-4">

                @if($settings['hospital_logo'])
                <div class="mb-3 text-center">
                    <img src="{{ Storage::url($settings['hospital_logo']) }}"
                         alt="Logo" class="img-thumbnail" style="max-height:120px;">
                    <div class="form-text mt-1">{{ __('Current logo') }}</div>
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="hospital_logo">
                        {{ $settings['hospital_logo'] ? __('Replace Logo') : __('Upload Logo') }}
                    </label>
                    <input id="hospital_logo" type="file" name="hospital_logo"
                           class="form-control @error('hospital_logo') is-invalid @enderror"
                           accept="image/*">
                    @error('hospital_logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">{{ __('PNG or JPG, max 2MB. Appears in the invoice header.') }}</div>
                </div>

                <div class="alert alert-light border small">
                    <i class="bi bi-info-circle ms-1"></i>
                    {{ __('The logo is embedded in PDF invoices. For best results use a PNG with transparent background (max 300×100px).') }}
                </div>
            </div>
        </div>
    </div>

</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg ms-1"></i> {{ __('Save Settings') }}
    </button>
</div>

</form>
@endsection
