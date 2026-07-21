<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Services\MedicationService;
use App\Services\ServiceCatalogService;
use Illuminate\View\View;

class CatalogHealthController extends Controller
{
    public function __construct(
        private readonly MedicationService $medicationService,
        private readonly ServiceCatalogService $serviceCatalogService,
    ) {}

    public function duplicateCodes(): View
    {
        $medicationGroups = $this->medicationService->duplicateCodeGroups();
        $serviceGroups     = $this->serviceCatalogService->duplicateCodeGroups();

        return view('catalog.duplicates.index', compact('medicationGroups', 'serviceGroups'));
    }
}
