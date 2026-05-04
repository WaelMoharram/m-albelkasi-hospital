<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Medication;
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
        $units = Unit::orderBy('name')->get();
        return view('catalog.medications.create', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'code'  => ['nullable', 'string', 'max:50'],
            'unit'  => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'type'  => ['required', 'in:local,imported'],
        ]);

        $this->service->create($data);

        alert()->success(__('Created'), __('Medication added successfully.'));

        return redirect()->route('catalog.medications.index');
    }

    public function edit(Medication $medication): View
    {
        $units = Unit::orderBy('name')->get();
        return view('catalog.medications.edit', compact('medication', 'units'));
    }

    public function update(Request $request, Medication $medication): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'code'  => ['nullable', 'string', 'max:50'],
            'unit'  => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'type'  => ['required', 'in:local,imported'],
        ]);

        $this->service->update($medication, $data);

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
