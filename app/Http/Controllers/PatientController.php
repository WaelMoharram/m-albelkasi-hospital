<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(private readonly PatientService $service) {}

    public function index(Request $request): View
    {
        $patients = $this->service->paginate($request->input('search'));

        return view('patients.index', compact('patients'));
    }

    public function create(): View
    {
        $insuranceCompanies = InsuranceCompany::orderBy('name')->pluck('name', 'id');

        return view('patients.create', compact('insuranceCompanies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'national_id'          => ['required', 'string', 'max:50', 'unique:patients,national_id'],
            'dob'                  => ['required', 'date', 'before:today'],
            'gender'               => ['required', 'in:male,female'],
            'insurance_company_id' => ['required', 'exists:insurance_companies,id'],
            'policy_number'        => ['required', 'string', 'max:100'],
        ]);

        $this->service->create($data);

        alert()->success('Registered', 'Patient registered successfully.');

        return redirect()->route('patients.index');
    }

    public function edit(Patient $patient): View
    {
        $insuranceCompanies = InsuranceCompany::orderBy('name')->pluck('name', 'id');

        return view('patients.edit', compact('patient', 'insuranceCompanies'));
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'national_id'          => ['required', 'string', 'max:50', "unique:patients,national_id,{$patient->id}"],
            'dob'                  => ['required', 'date', 'before:today'],
            'gender'               => ['required', 'in:male,female'],
            'insurance_company_id' => ['required', 'exists:insurance_companies,id'],
            'policy_number'        => ['required', 'string', 'max:100'],
        ]);

        $this->service->update($patient, $data);

        alert()->success('Updated', 'Patient updated successfully.');

        return redirect()->route('patients.index');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->service->delete($patient);

        alert()->success('Deleted', 'Patient removed.');

        return redirect()->route('patients.index');
    }
}
