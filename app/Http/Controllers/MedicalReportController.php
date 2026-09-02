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

        $data['report_date'] ??= $admission->admission_date->toDateString();

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

        $data['report_date'] ??= $admission->admission_date->toDateString();

        $this->service->create($admission, MedicalReportType::Radiology, $data, auth()->id());

        alert()->success(__('Created'), __('Radiology report added successfully.'));

        return redirect()->route('invoices.show', $admission->invoice);
    }

    public function updateInpatient(Request $request, MedicalReport $medicalReport): RedirectResponse
    {
        $data = $request->validate([
            'report_date' => ['nullable', 'date'],
            'referring_doctor' => ['nullable', 'string', 'max:150'],
            'diagnosis' => ['nullable', 'string'],
            'procedure_notes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $data['report_date'] ??= $medicalReport->admission->admission_date->toDateString();

        $this->service->update($medicalReport, $data);

        alert()->success(__('Updated'), __('Medical report updated successfully.'));

        return redirect()->route('invoices.show', $medicalReport->admission->invoice);
    }

    public function updateRadiology(Request $request, MedicalReport $medicalReport): RedirectResponse
    {
        $data = $request->validate([
            'report_date' => ['nullable', 'date'],
            'exam_type' => ['nullable', 'string', 'max:150'],
            'referring_doctor' => ['nullable', 'string', 'max:150'],
            'diagnosis' => ['nullable', 'string'],
            'procedure_notes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $data['report_date'] ??= $medicalReport->admission->admission_date->toDateString();

        $this->service->update($medicalReport, $data);

        alert()->success(__('Updated'), __('Radiology report updated successfully.'));

        return redirect()->route('invoices.show', $medicalReport->admission->invoice);
    }

    public function destroy(MedicalReport $medicalReport): RedirectResponse
    {
        $invoice = $medicalReport->admission->invoice;

        $this->service->delete($medicalReport);

        alert()->success(__('Deleted'), __('Report removed.'));

        return redirect()->route('invoices.show', $invoice);
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
