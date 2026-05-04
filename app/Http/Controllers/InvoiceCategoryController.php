<?php

namespace App\Http\Controllers;

use App\Models\InvoiceCategory;
use App\Services\InvoiceCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceCategoryController extends Controller
{
    public function __construct(private readonly InvoiceCategoryService $service) {}

    public function index(): View
    {
        $categories = $this->service->all();
        return view('catalog.invoice-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('catalog.invoice-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $this->service->create($data);
        alert()->success(__('Created'), __('Category added successfully.'));
        return redirect()->route('catalog.invoice-categories.index');
    }

    public function edit(InvoiceCategory $invoiceCategory): View
    {
        return view('catalog.invoice-categories.edit', ['category' => $invoiceCategory]);
    }

    public function update(Request $request, InvoiceCategory $invoiceCategory): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $this->service->update($invoiceCategory, $data);
        alert()->success(__('Updated'), __('Category updated successfully.'));
        return redirect()->route('catalog.invoice-categories.index');
    }

    public function destroy(InvoiceCategory $invoiceCategory): RedirectResponse
    {
        $this->service->delete($invoiceCategory);
        alert()->success(__('Deleted'), __('Category removed.'));
        return redirect()->route('catalog.invoice-categories.index');
    }
}
