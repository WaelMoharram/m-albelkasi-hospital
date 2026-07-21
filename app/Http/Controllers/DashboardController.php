<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Invoice;
use App\Models\Patient;
use App\Services\MedicationService;
use App\Services\ServiceCatalogService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MedicationService $medicationService,
        private readonly ServiceCatalogService $serviceCatalogService,
    ) {}

    public function index(): View
    {
        $stats = [
            'patients'          => Patient::count(),
            'active_admissions' => Admission::where('status', 'active')->count(),
            'draft_invoices'    => Invoice::where('status', 'draft')->count(),
        ];

        $duplicateCodeGroups = $this->medicationService->duplicateCodeGroups()->count()
            + $this->serviceCatalogService->duplicateCodeGroups()->count();

        return view('dashboard.index', compact('stats', 'duplicateCodeGroups'));
    }
}
