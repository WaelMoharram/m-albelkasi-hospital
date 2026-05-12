<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Admission;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\Ward;
use App\Services\AdmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function __construct(private readonly AdmissionService $service) {}

    public function index(Request $request): View
    {
        $admissions = $this->service->paginate(
            search: $request->input('search'),
            status: $request->input('status'),
        );

        return view('admissions.index', compact('admissions'));
    }

    public function create(Request $request): View
    {
        // Allow pre-filling patient_id from query string (e.g. from patient profile)
        $patients           = Patient::orderBy('name')->get(['id', 'name', 'national_id']);
        $selectedPatient    = $request->input('patient_id');
        $insuranceCompanies = InsuranceCompany::orderBy('name')->pluck('name');
        $wards              = Ward::with('rooms')->orderBy('name')->get();

        return view('admissions.create', compact('patients', 'selectedPatient', 'insuranceCompanies', 'wards'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id'      => ['required', 'exists:patients,id'],
            'admission_date'  => ['required', 'date', 'before_or_equal:today'],
            'room'            => ['nullable', 'string', 'max:50'],
            'ward'            => ['nullable', 'string', 'max:100'],
            'referral_number' => ['nullable', 'string', 'max:50', 'unique:admissions,referral_number'],
            'referral_source' => ['nullable', 'string', 'max:100'],
        ]);

        $admission = $this->service->create($data);

        alert()->success(__('Admitted'), __('Patient admitted and daily services scheduled.'));

        return redirect()->route('admissions.show', $admission);
    }

    public function show(Admission $admission): View
    {
        $admission->load([
            'patient.insuranceCompany',
            'invoice.items.itemable',
        ]);

        return view('admissions.show', compact('admission'));
    }

    public function edit(Admission $admission): View
    {
        $patients           = Patient::orderBy('name')->get(['id', 'name', 'national_id']);
        $insuranceCompanies = InsuranceCompany::orderBy('name')->pluck('name');
        $wards              = Ward::with('rooms')->orderBy('name')->get();

        return view('admissions.edit', compact('admission', 'patients', 'insuranceCompanies', 'wards'));
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $data = $request->validate([
            'patient_id'      => ['required', 'exists:patients,id'],
            'admission_date'  => ['required', 'date'],
            'room'            => ['nullable', 'string', 'max:50'],
            'ward'            => ['nullable', 'string', 'max:100'],
            'referral_number' => ['nullable', 'string', 'max:50', Rule::unique('admissions', 'referral_number')->ignore($admission->id)],
            'referral_source' => ['nullable', 'string', 'max:100'],
        ]);

        $this->service->update($admission, $data);

        alert()->success(__('Updated'), __('Admission updated successfully.'));

        return redirect()->route('admissions.show', $admission);
    }

    public function destroy(Admission $admission): RedirectResponse
    {
        $this->authorize(Permission::DeleteAdmissions->value);

        $this->service->delete($admission);

        alert()->success(__('Deleted'), __('Admission deleted successfully.'));

        return redirect()->route('admissions.index');
    }

    public function discharge(Request $request, Admission $admission): RedirectResponse
    {
        if (! $admission->isActive()) {
            alert()->warning(__('Already Discharged'), __('This admission has already been discharged.'));

            return redirect()->route('admissions.show', $admission);
        }

        $data = $request->validate([
            'discharge_date'   => ['required', 'date', 'after_or_equal:' . $admission->admission_date->toDateString(), 'before_or_equal:today'],
            'discharge_reason' => ['required', 'in:discharged,died,transferred'],
        ]);

        $this->service->discharge($admission, $data['discharge_date'], $data['discharge_reason']);

        alert()->success(__('Discharged'), __('Patient discharged and invoice finalised.'));

        return redirect()->route('admissions.show', $admission);
    }
}
