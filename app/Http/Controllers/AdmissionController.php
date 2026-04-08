<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Patient;
use App\Services\AdmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $patients        = Patient::orderBy('name')->get(['id', 'name', 'national_id']);
        $selectedPatient = $request->input('patient_id');

        return view('admissions.create', compact('patients', 'selectedPatient'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id'     => ['required', 'exists:patients,id'],
            'admission_date' => ['required', 'date', 'before_or_equal:today'],
            'room'           => ['nullable', 'string', 'max:50'],
            'ward'           => ['nullable', 'string', 'max:100'],
        ]);

        $admission = $this->service->create($data);

        alert()->success('Admitted', 'Patient admitted and daily services scheduled.');

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
        $patients = Patient::orderBy('name')->get(['id', 'name', 'national_id']);

        return view('admissions.edit', compact('admission', 'patients'));
    }

    public function update(Request $request, Admission $admission): RedirectResponse
    {
        $data = $request->validate([
            'patient_id'     => ['required', 'exists:patients,id'],
            'admission_date' => ['required', 'date'],
            'room'           => ['nullable', 'string', 'max:50'],
            'ward'           => ['nullable', 'string', 'max:100'],
        ]);

        $this->service->update($admission, $data);

        alert()->success('Updated', 'Admission updated successfully.');

        return redirect()->route('admissions.show', $admission);
    }

    public function discharge(Request $request, Admission $admission): RedirectResponse
    {
        if (! $admission->isActive()) {
            alert()->warning('Already Discharged', 'This admission has already been discharged.');

            return redirect()->route('admissions.show', $admission);
        }

        $data = $request->validate([
            'discharge_date' => [
                'required',
                'date',
                'after_or_equal:' . $admission->admission_date->toDateString(),
                'before_or_equal:today',
            ],
        ]);

        $this->service->discharge($admission, $data['discharge_date']);

        alert()->success('Discharged', 'Patient discharged and invoice finalised.');

        return redirect()->route('admissions.show', $admission);
    }
}
