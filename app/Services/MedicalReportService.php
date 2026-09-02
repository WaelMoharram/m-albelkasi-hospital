<?php

namespace App\Services;

use App\Enums\MedicalReportType;
use App\Models\Admission;
use App\Models\MedicalReport;
use Illuminate\Database\Eloquent\Collection;

class MedicalReportService
{
    public function create(Admission $admission, MedicalReportType $type, array $data, ?int $createdBy): MedicalReport
    {
        return $admission->medicalReports()->create([
            ...$data,
            'type' => $type,
            'created_by' => $createdBy,
        ]);
    }

    public function forAdmission(Admission $admission, MedicalReportType $type): Collection
    {
        return $admission->medicalReports()
            ->where('type', $type)
            ->latest()
            ->get();
    }

    public function update(MedicalReport $report, array $data): MedicalReport
    {
        $report->update($data);

        return $report;
    }

    public function delete(MedicalReport $report): void
    {
        $report->delete();
    }
}
