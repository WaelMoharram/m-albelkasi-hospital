<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'patients'          => Patient::count(),
            'active_admissions' => Admission::where('status', 'active')->count(),
            'draft_invoices'    => Invoice::where('status', 'draft')->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }
}
