<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use App\Models\Setting;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function index(Request $request): View
    {
        [$month, $year] = $this->resolveMonthYear($request);

        $rows   = $this->service->monthlyReport($month, $year);
        $totals = $this->service->columnTotals($rows);

        return view('reports.index', compact('rows', 'totals', 'month', 'year'));
    }

    public function export(Request $request): View
    {
        [$month, $year] = $this->resolveMonthYear($request);

        $rows   = $this->service->monthlyReport($month, $year);
        $totals = $this->service->columnTotals($rows);

        return view('reports.monthly_a3', compact('rows', 'totals', 'month', 'year'));
    }

    // ── Patient List (ح) ─────────────────────────────────────────────────────

    public function patientList(Request $request): View
    {
        $companies = InsuranceCompany::orderBy('name')->get();
        $data      = null;

        if ($request->filled('insurance_company_id')) {
            [$month, $year] = $this->resolveMonthYear($request);
            $data = $this->service->getPatientListData($month, $year, (int) $request->input('insurance_company_id'));
        }

        return view('reports.patient_list', compact('companies', 'data'));
    }

    public function patientListPrint(Request $request): View
    {
        $request->validate(['insurance_company_id' => ['required', 'integer', 'exists:insurance_companies,id']]);

        [$month, $year] = $this->resolveMonthYear($request);
        $data      = $this->service->getPatientListData($month, $year, (int) $request->input('insurance_company_id'));
        $settings  = Setting::pluck('value', 'key');
        $logo      = $this->buildLogo($settings);
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('ar')->isoFormat('MMMM');

        return view('reports.patient_list_print', array_merge($data, compact('settings', 'logo', 'monthName')));
    }

    // ── Claim Sheet (كشف المطالبة) ────────────────────────────────────────────

    public function claim(Request $request): View
    {
        $companies = InsuranceCompany::orderBy('name')->get();
        $data      = null;

        if ($request->filled('insurance_company_id')) {
            [$month, $year] = $this->resolveMonthYear($request);
            $data = $this->service->getClaimData($month, $year, (int) $request->input('insurance_company_id'));
        }

        return view('reports.claim', compact('companies', 'data'));
    }

    public function claimPrint(Request $request): View
    {
        $request->validate(['insurance_company_id' => ['required', 'integer', 'exists:insurance_companies,id']]);

        [$month, $year] = $this->resolveMonthYear($request);
        $data      = $this->service->getClaimData($month, $year, (int) $request->input('insurance_company_id'));
        $settings  = Setting::pluck('value', 'key');
        $logo      = $this->buildLogo($settings);
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('ar')->isoFormat('MMMM');

        return view('reports.claim_print', array_merge($data, compact('settings', 'logo', 'monthName')));
    }

    // ── Summary (المجمع) ──────────────────────────────────────────────────────

    public function summary(Request $request): View
    {
        $companies = InsuranceCompany::orderBy('name')->get();
        $data      = null;

        if ($request->filled('insurance_company_id')) {
            [$month, $year] = $this->resolveMonthYear($request);
            $data = $this->service->getSummaryData($month, $year, (int) $request->input('insurance_company_id'));
        }

        return view('reports.summary', compact('companies', 'data'));
    }

    public function summaryPrint(Request $request): View
    {
        $request->validate(['insurance_company_id' => ['required', 'integer', 'exists:insurance_companies,id']]);

        [$month, $year] = $this->resolveMonthYear($request);
        $data      = $this->service->getSummaryData($month, $year, (int) $request->input('insurance_company_id'));
        $settings  = Setting::pluck('value', 'key');
        $logo      = $this->buildLogo($settings);
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('ar')->isoFormat('MMMM');

        return view('reports.summary_print', array_merge($data, compact('settings', 'logo', 'monthName')));
    }

    // ── Performance Indicators (مؤشرات الأداء) ────────────────────────────────

    public function performance(Request $request): View
    {
        $data = null;

        if ($request->filled('period') || $request->filled('month')) {
            [$month, $year] = $this->resolveMonthYear($request);
            $data = $this->service->getPerformanceData($month, $year);
        }

        return view('reports.performance', compact('data'));
    }

    public function performancePrint(Request $request): View
    {
        [$month, $year] = $this->resolveMonthYear($request);
        $data      = $this->service->getPerformanceData($month, $year);
        $settings  = Setting::pluck('value', 'key');
        $logo      = $this->buildLogo($settings);
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('ar')->isoFormat('MMMM');

        return view('reports.performance_print', array_merge($data, compact('settings', 'logo', 'monthName')));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildLogo(Collection $settings): ?string
    {
        $path = $settings->get('hospital_logo');
        if (! $path || ! file_exists(storage_path('app/public/' . $path))) {
            return null;
        }
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
        return "data:{$mime};base64," . base64_encode(file_get_contents(storage_path('app/public/' . $path)));
    }

    private function resolveMonthYear(Request $request): array
    {
        if ($request->filled('period')) {
            [$year, $month] = explode('-', $request->input('period'));
        } else {
            $month = (int) $request->input('month', now()->month);
            $year  = (int) $request->input('year',  now()->year);
        }

        $month = max(1, min(12, (int) $month));
        $year  = max(2000, min((int) now()->year + 1, (int) $year));

        return [$month, $year];
    }
}
