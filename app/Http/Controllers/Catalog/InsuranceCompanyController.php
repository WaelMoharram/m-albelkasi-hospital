<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Services\InsuranceCompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsuranceCompanyController extends Controller
{
    public function __construct(private readonly InsuranceCompanyService $service) {}

    public function index(Request $request): View
    {
        $companies = $this->service->paginate($request->input('search'));

        return view('catalog.insurance.index', compact('companies'));
    }

    public function create(): View
    {
        return view('catalog.insurance.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'contact_info' => ['nullable', 'string'],
        ]);

        $this->service->create($data);

        alert()->success('Created', 'Insurance company added successfully.');

        return redirect()->route('catalog.insurance.index');
    }

    public function edit(InsuranceCompany $insuranceCompany): View
    {
        return view('catalog.insurance.edit', compact('insuranceCompany'));
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'contact_info' => ['nullable', 'string'],
        ]);

        $this->service->update($insuranceCompany, $data);

        alert()->success('Updated', 'Insurance company updated successfully.');

        return redirect()->route('catalog.insurance.index');
    }

    public function destroy(InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $this->service->delete($insuranceCompany);

        alert()->success('Deleted', 'Insurance company removed.');

        return redirect()->route('catalog.insurance.index');
    }
}
