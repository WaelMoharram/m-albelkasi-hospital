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
        $category = in_array($request->input('category'), ['supplies', 'lab', 'radiology', 'other']) ? $request->input('category') : null;
        $isDaily  = $request->input('is_daily');
        $services = $this->service->paginate($request->input('search'), $category, $isDaily, $request->input('is_once'));

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
            'code'                => ['nullable', 'string', 'max:50'],
            'price'               => ['required', 'numeric', 'min:0'],
            'category'            => ['required', 'in:supplies,lab,radiology,other'],
            'invoice_category_id' => ['nullable', 'integer', 'exists:invoice_categories,id'],
            'is_daily'            => ['nullable', 'boolean'],
            'daily_qty'           => ['nullable', 'integer', 'min:1', 'max:99'],
            'is_once'             => ['nullable', 'boolean'],
        ]);
        $data['is_daily']  = $request->boolean('is_daily');
        $data['daily_qty'] = $request->boolean('is_daily') ? max(1, (int) $request->input('daily_qty', 1)) : 1;
        $data['is_once']   = $request->boolean('is_once');

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
            'code'                => ['nullable', 'string', 'max:50'],
            'price'               => ['required', 'numeric', 'min:0'],
            'category'            => ['required', 'in:supplies,lab,radiology,other'],
            'invoice_category_id' => ['nullable', 'integer', 'exists:invoice_categories,id'],
            'is_daily'            => ['nullable', 'boolean'],
            'daily_qty'           => ['nullable', 'integer', 'min:1', 'max:99'],
            'is_once'             => ['nullable', 'boolean'],
        ]);
        $data['is_daily']  = $request->boolean('is_daily');
        $data['daily_qty'] = $request->boolean('is_daily') ? max(1, (int) $request->input('daily_qty', 1)) : 1;
        $data['is_once']   = $request->boolean('is_once');

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
