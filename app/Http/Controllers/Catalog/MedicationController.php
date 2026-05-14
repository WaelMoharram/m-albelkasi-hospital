<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Service;
use App\Models\Unit;
use App\Services\MedicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicationController extends Controller
{
    public function __construct(private readonly MedicationService $service) {}

    public function index(Request $request): View
    {
        $type        = in_array($request->input('type'), ['local', 'imported']) ? $request->input('type') : null;
        $medications = $this->service->paginate($request->input('search'), $type);

        return view('catalog.medications.index', compact('medications'));
    }

    public function create(): View
    {
        $units       = Unit::orderBy('name')->get();
        $allServices = Service::orderBy('name')->get();

        return view('catalog.medications.create', compact('units', 'allServices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['nullable', 'string', 'max:50'],
            'unit'      => ['nullable', 'string', 'max:100'],
            'price'     => ['required', 'numeric', 'min:0'],
            'type'      => ['required', 'in:local,imported'],
            'daily_qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $data['daily_qty'] = max(1, (int) $request->input('daily_qty', 1));

        $medication = $this->service->create($data);
        $this->service->syncTriggers($medication, $request->input('triggers', []));

        alert()->success(__('Created'), __('Medication added successfully.'));

        return redirect()->route('catalog.medications.index');
    }

    public function edit(Medication $medication): View
    {
        $units       = Unit::orderBy('name')->get();
        $allServices = Service::orderBy('name')->get();
        $medication->load('triggeredServices');

        return view('catalog.medications.edit', compact('medication', 'units', 'allServices'));
    }

    public function update(Request $request, Medication $medication): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['nullable', 'string', 'max:50'],
            'unit'      => ['nullable', 'string', 'max:100'],
            'price'     => ['required', 'numeric', 'min:0'],
            'type'      => ['required', 'in:local,imported'],
            'daily_qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $data['daily_qty'] = max(1, (int) $request->input('daily_qty', 1));

        $this->service->update($medication, $data);
        $this->service->syncTriggers($medication, $request->input('triggers', []));

        alert()->success(__('Updated'), __('Medication updated successfully.'));

        return redirect()->route('catalog.medications.index');
    }

    public function destroy(Medication $medication): RedirectResponse
    {
        $this->service->delete($medication);

        alert()->success(__('Deleted'), __('Medication removed.'));

        return redirect()->route('catalog.medications.index');
    }
}
