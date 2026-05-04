<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\InsuranceCompany;
use App\Models\InvoiceCategory;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Summary report (المجمع): groups by ward + policy_number for one insurance company.
     */
    public function getSummaryData(int $month, int $year, int $insuranceCompanyId): array
    {
        $admissions = Admission::with(['patient', 'invoice'])
            ->whereHas('patient', fn ($q) => $q->where('insurance_company_id', $insuranceCompanyId))
            ->whereNotNull('discharge_date')
            ->whereMonth('discharge_date', $month)
            ->whereYear('discharge_date', $year)
            ->get();

        $groups = $admissions->groupBy(fn ($a) =>
            ($a->ward ?? 'غير محدد') . '|||' . ($a->patient->policy_number ?? 'غير محدد')
        );

        $rows = $groups->values()->map(function ($group, int $idx) {
            $sample = $group->first();
            $days   = $group->sum(fn ($a) =>
                max(1, (int) $a->admission_date->diffInDays($a->discharge_date))
            );
            $amount = $group->sum(fn ($a) => (float) ($a->invoice?->total_amount ?? 0));

            return [
                'seq'          => $idx + 1,
                'service_type' => $sample->ward ?? 'غير محدد',
                'law'          => $sample->patient->policy_number ?? 'غير محدد',
                'count'        => $group->count(),
                'days'         => $days,
                'amount'       => round($amount, 3),
            ];
        });

        return [
            'rows'      => $rows,
            'totals'    => [
                'count'  => $rows->sum('count'),
                'days'   => $rows->sum('days'),
                'amount' => round($rows->sum('amount'), 3),
            ],
            'insurance' => InsuranceCompany::find($insuranceCompanyId),
            'month'     => $month,
            'year'      => $year,
        ];
    }

    /**
     * Performance indicators (مؤشرات الأداء) for one month across all patients.
     */
    public function getPerformanceData(int $month, int $year): array
    {
        $daysInMonth    = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $icuBeds        = (int) Setting::getValue('icu_beds', 6);
        $availableDays  = $daysInMonth * $icuBeds;

        $admissions = Admission::whereNotNull('discharge_date')
            ->whereMonth('discharge_date', $month)
            ->whereYear('discharge_date', $year)
            ->get();

        $patientCount = $admissions->count();
        $stayDays     = $admissions->sum(fn ($a) =>
            max(1, (int) $a->admission_date->diffInDays($a->discharge_date))
        );
        $remainingDays  = $availableDays - $stayDays;
        $deaths         = $admissions->where('discharge_reason', 'died')->count();
        $deaths24h      = $admissions->filter(fn ($a) =>
            $a->discharge_reason === 'died' &&
            $a->admission_date->diffInDays($a->discharge_date) <= 1
        )->count();

        $avgDailyFreq   = $daysInMonth > 0  ? round($patientCount / $daysInMonth, 6) : 0;
        $mortalityRate  = $patientCount > 0 ? round($deaths        / $patientCount, 6) : 0;
        $avgStay        = $patientCount > 0 ? round($stayDays      / $patientCount, 6) : 0;
        $bedTurnover    = $icuBeds > 0      ? round($patientCount  / $icuBeds, 6) : 0;
        $occupancyRate  = $availableDays > 0 ? round($stayDays     / $availableDays, 6) : 0;

        return [
            'month'          => $month,
            'year'           => $year,
            'days_in_month'  => $daysInMonth,
            'icu_beds'       => $icuBeds,
            'available_days' => $availableDays,
            'patient_count'  => $patientCount,
            'stay_days'      => $stayDays,
            'remaining_days' => $remainingDays,
            'deaths'         => $deaths,
            'deaths_24h'     => $deaths24h,
            'avg_daily_freq' => $avgDailyFreq,
            'mortality_rate' => $mortalityRate,
            'avg_stay'       => $avgStay,
            'bed_turnover'   => $bedTurnover,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    /**
     * Build data for the patient list summary (ح المطالبة).
     * One row per admission: name, dob, age, dates, days, referral, invoice total, per-day rate.
     */
    public function getPatientListData(int $month, int $year, int $insuranceCompanyId): array
    {
        $admissions = Admission::with(['patient.insuranceCompany', 'invoice'])
            ->whereHas('patient', fn ($q) => $q->where('insurance_company_id', $insuranceCompanyId))
            ->whereNotNull('discharge_date')
            ->whereMonth('discharge_date', $month)
            ->whereYear('discharge_date', $year)
            ->orderBy('admission_date')
            ->get();

        $rows = $admissions->values()->map(function (Admission $admission, int $idx) {
            $days  = max(1, (int) $admission->admission_date->diffInDays($admission->discharge_date));
            $total = (float) ($admission->invoice?->total_amount ?? 0);
            $age   = $admission->patient->dob
                ? (int) $admission->patient->dob->diffInYears($admission->admission_date)
                : null;

            return [
                'seq'             => $idx + 1,
                'patient'         => $admission->patient,
                'admission'       => $admission,
                'age'             => $age,
                'days'            => $days,
                'referral_number' => $admission->referral_number,
                'referral_source' => $admission->referral_source,
                'invoice_total'   => $total,
                'per_day'         => $days > 0 ? round($total / $days, 6) : 0,
            ];
        });

        return [
            'rows'      => $rows,
            'totals'    => [
                'days'          => $rows->sum('days'),
                'invoice_total' => round($rows->sum('invoice_total'), 3),
            ],
            'insurance' => InsuranceCompany::find($insuranceCompanyId),
            'month'     => $month,
            'year'      => $year,
        ];
    }

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
     * Build data for the insurance claim sheet (كشف المطالبة).
     * Groups invoice items by invoice_category for the 7 stay sub-columns,
     * then separates labs and medications (with discounts applied).
     */
    public function getClaimData(int $month, int $year, int $insuranceCompanyId): array
    {
        $categories       = InvoiceCategory::ordered()->get();
        $localDiscount    = (float) Setting::getValue('local_med_discount',    0) / 100;
        $importedDiscount = (float) Setting::getValue('imported_med_discount', 0) / 100;

        $admissions = Admission::with([
            'patient.insuranceCompany',
            'invoice.items.itemable',
        ])
        ->whereHas('patient', fn ($q) => $q->where('insurance_company_id', $insuranceCompanyId))
        ->whereNotNull('discharge_date')
        ->whereMonth('discharge_date', $month)
        ->whereYear('discharge_date', $year)
        ->orderBy('admission_date')
        ->get();

        $rows = $admissions->values()->map(function (Admission $admission, int $idx) use ($categories, $localDiscount, $importedDiscount) {
            $items = $admission->invoice?->items ?? collect();

            $byCategory = [];
            foreach ($categories as $cat) {
                $byCategory[$cat->id] = (float) $items->filter(
                    fn ($item) =>
                        $item->itemable_type === 'App\\Models\\Service' &&
                        optional($item->itemable)->invoice_category_id === $cat->id
                )->sum('total');
            }

            $staySubtotal = (float) array_sum($byCategory);
            $labs         = (float) $items->where('section', 'lab')->sum('total');
            $localRaw     = (float) $items->where('section', 'local_med')->sum('total');
            $importedRaw  = (float) $items->where('section', 'imported_med')->sum('total');
            $localMeds    = round($localRaw    * (1 - $localDiscount),    2);
            $importedMeds = round($importedRaw * (1 - $importedDiscount), 2);
            $grandTotal   = $staySubtotal + $labs + $localMeds + $importedMeds;
            $days         = max(1, (int) $admission->admission_date->diffInDays($admission->discharge_date));

            return [
                'seq'           => $idx + 1,
                'admission'     => $admission,
                'patient'       => $admission->patient,
                'days'          => $days,
                'by_category'   => $byCategory,
                'stay_subtotal' => $staySubtotal,
                'labs'          => $labs,
                'local_meds'    => $localMeds,
                'imported_meds' => $importedMeds,
                'supplies'      => 0,
                'grand_total'   => $grandTotal,
                'per_day'       => $days > 0 ? round($grandTotal / $days, 6) : 0,
            ];
        });

        $totals = [
            'days'          => $rows->sum('days'),
            'stay_subtotal' => round($rows->sum('stay_subtotal'), 2),
            'labs'          => round($rows->sum('labs'), 2),
            'local_meds'    => round($rows->sum('local_meds'), 2),
            'imported_meds' => round($rows->sum('imported_meds'), 2),
            'supplies'      => 0,
            'grand_total'   => round($rows->sum('grand_total'), 2),
            'by_category'   => [],
        ];
        foreach ($categories as $cat) {
            $totals['by_category'][$cat->id] = round(
                $rows->sum(fn ($r) => $r['by_category'][$cat->id] ?? 0), 2
            );
        }

        return [
            'rows'              => $rows,
            'totals'            => $totals,
            'categories'        => $categories,
            'insurance'         => InsuranceCompany::find($insuranceCompanyId),
            'month'             => $month,
            'year'              => $year,
            'local_discount'    => $localDiscount    * 100,
            'imported_discount' => $importedDiscount * 100,
        ];
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
