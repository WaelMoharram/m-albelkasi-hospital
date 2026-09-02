<?php

namespace App\Http\Controllers;

use App\Enums\MedicalReportType;
use App\Models\Admission;
use App\Models\MedicalReport;
use App\Services\MedicalReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalReportController extends Controller
{
    public function __construct(private readonly MedicalReportService $service) {}

    public function storeInpatient(Request $request, Admission $admission): RedirectResponse
    {
        $data = $request->validate([
            'report_date' => ['nullable', 'date'],
            'referring_doctor' => ['nullable', 'string', 'max:150'],
            'diagnosis' => ['nullable', 'string'],
            'procedure_notes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $data['report_date'] ??= now()->toDateString();

        $this->service->create($admission, MedicalReportType::Inpatient, $data, auth()->id());

        alert()->success(__('Created'), __('Medical report added successfully.'));

        return redirect()->route('invoices.show', $admission->invoice);
    }

    public function storeRadiology(Request $request, Admission $admission): RedirectResponse
    {
        $data = $request->validate([
            'report_date' => ['nullable', 'date'],
            'exam_type' => ['nullable', 'string', 'max:150'],
            'referring_doctor' => ['nullable', 'string', 'max:150'],
            'diagnosis' => ['nullable', 'string'],
            'procedure_notes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $data['report_date'] ??= now()->toDateString();

        $this->service->create($admission, MedicalReportType::Radiology, $data, auth()->id());

        alert()->success(__('Created'), __('Radiology report added successfully.'));

        return redirect()->route('invoices.show', $admission->invoice);
    }

    public function print(MedicalReport $medicalReport): View
    {
        $medicalReport->load('admission.patient.insuranceCompany');

        $view = match ($medicalReport->type) {
            MedicalReportType::Inpatient => 'medical_reports.print_inpatient',
            MedicalReportType::Radiology => 'medical_reports.print_radiology',
        };

        return view($view, ['report' => $medicalReport]);
    }
}
