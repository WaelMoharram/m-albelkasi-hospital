<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\InvoiceCategory;
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
        $allServices       = Service::orderBy('name')->get();
        $invoiceCategories = InvoiceCategory::ordered()->get();

        return view('catalog.services.create', compact('allServices', 'invoiceCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'price'               => ['required', 'numeric', 'min:0'],
            'category'            => ['required', 'in:daily,lab,radiology'],
            'invoice_category_id' => ['nullable', 'integer', 'exists:invoice_categories,id'],
        ]);

        $service = $this->service->create($data);
        $this->service->syncTriggers($service, $request->input('triggers', []));

        alert()->success(__('Created'), __('Service added successfully.'));
        return redirect()->route('catalog.services.index');
    }

    public function edit(Service $service): View
    {
        $allServices       = Service::where('id', '!=', $service->id)->orderBy('name')->get();
        $invoiceCategories = InvoiceCategory::ordered()->get();
        $service->load('triggers');

        return view('catalog.services.edit', compact('service', 'allServices', 'invoiceCategories'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'price'               => ['required', 'numeric', 'min:0'],
            'category'            => ['required', 'in:daily,lab,radiology'],
            'invoice_category_id' => ['nullable', 'integer', 'exists:invoice_categories,id'],
        ]);

        $this->service->update($service, $data);
        $this->service->syncTriggers($service, $request->input('triggers', []));

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
