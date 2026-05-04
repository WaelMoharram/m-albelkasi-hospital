<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(private readonly UnitService $service) {}

    public function index(): View
    {
        return view('catalog.units.index', [
            'units' => $this->service->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:units,name'],
        ]);

        $this->service->create($data);

        alert()->success(__('Created'), __('Unit added successfully.'));

        return redirect()->route('catalog.units.index');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:units,name,' . $unit->id],
        ]);

        $this->service->update($unit, $data);

        alert()->success(__('Updated'), __('Unit updated successfully.'));

        return redirect()->route('catalog.units.index');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->service->delete($unit);

        alert()->success(__('Deleted'), __('Unit removed.'));

        return redirect()->route('catalog.units.index');
    }
}
