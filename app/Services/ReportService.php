<?php

namespace App\Services;

use App\Models\Admission;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Fetch all admissions whose admission_date falls in the given month/year,
     * then aggregate their invoice items by section into a flat row collection.
     *
     * Each row is an array with keys:
     *   admission, patient, insurance,
     *   local_med, imported_med, lab, radiology, daily, grand_total
     */
    public function monthlyReport(int $month, int $year): Collection
    {
        $admissions = Admission::with([
            'patient.insuranceCompany',
            'invoice.items',
        ])
        ->whereMonth('admission_date', $month)
        ->whereYear('admission_date', $year)
        ->orderBy('admission_date')
        ->orderBy('id')
        ->get();

        return $admissions->map(function (Admission $admission) {
            $items = $admission->invoice?->items ?? collect();

            $sectionTotal = fn (string $section): float =>
                (float) $items->where('section', $section)->sum('total');

            $localMed    = $sectionTotal('local_med');
            $importedMed = $sectionTotal('imported_med');
            $lab         = $sectionTotal('lab');
            $radiology   = $sectionTotal('radiology');
            $daily       = $sectionTotal('daily');

            return [
                'admission'    => $admission,
                'patient'      => $admission->patient,
                'insurance'    => $admission->patient->insuranceCompany,
                'local_med'    => $localMed,
                'imported_med' => $importedMed,
                'lab'          => $lab,
                'radiology'    => $radiology,
                'daily'        => $daily,
                'grand_total'  => $localMed + $importedMed + $lab + $radiology + $daily,
            ];
        });
    }

    /**
     * Column-level totals for the summary footer row.
     */
    public function columnTotals(Collection $rows): array
    {
        $sum = fn (string $key): float => (float) $rows->sum($key);

        return [
            'local_med'    => $sum('local_med'),
            'imported_med' => $sum('imported_med'),
            'lab'          => $sum('lab'),
            'radiology'    => $sum('radiology'),
            'daily'        => $sum('daily'),
            'grand_total'  => $sum('grand_total'),
        ];
    }
}
