<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function export(Request $request): Response
    {
        [$month, $year] = $this->resolveMonthYear($request);

        $rows   = $this->service->monthlyReport($month, $year);
        $totals = $this->service->columnTotals($rows);

        $pdf = Pdf::loadView('reports.monthly_a3', compact('rows', 'totals', 'month', 'year'))
            ->setPaper('a3', 'landscape')
            ->setOptions([
                'isPhpEnabled'      => true,   // needed for dynamic page numbers
                'isRemoteEnabled'   => false,
                'defaultFont'       => 'DejaVu Sans',
                'dpi'               => 150,
            ]);

        $filename = sprintf('monthly-report-%04d-%02d.pdf', $year, $month);

        return $pdf->stream($filename);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function resolveMonthYear(Request $request): array
    {
        // Accept either separate month/year params or a combined YYYY-MM string
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
