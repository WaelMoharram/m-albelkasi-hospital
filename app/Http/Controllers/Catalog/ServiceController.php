<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ServiceCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceCatalogService $service) {}

    public function index(Request $request): View
    {
        $services = $this->service->paginate($request->input('search'));

        return view('catalog.services.index', compact('services'));
    }

    public function create(): View
    {
        return view('catalog.services.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'price'    => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'in:daily,lab,radiology'],
        ]);

        $this->service->create($data);

        alert()->success(__('Created'), __('Service added successfully.'));

        return redirect()->route('catalog.services.index');
    }

    public function edit(Service $service): View
    {
        return view('catalog.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'price'    => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'in:daily,lab,radiology'],
        ]);

        $this->service->update($service, $data);

        alert()->success(__('Updated'), __('Service updated successfully.'));

        return redirect()->route('catalog.services.index');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->service->delete($service);

        alert()->success(__('Deleted'), __('Service removed.'));

        return redirect()->route('catalog.services.index');
    }
}
