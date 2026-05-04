<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medication;
use App\Models\Service;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use LogicException;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $service) {}

    public function index(Request $request): View
    {
        $invoices = $this->service->paginate(
            search: $request->input('search'),
            status: $request->input('status'),
        );

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'admission.patient.insuranceCompany',
            'items.itemable',
        ]);

        // Pre-encode catalog as JSON for the add-item modal JS (draft only).
        // Encoding is done here — never use @json() with arrow functions in Blade,
        // as the Blade compiler misreads the closing ) inside fn($x) =>.
        $catalogJson = '{}';

        if ($invoice->status === 'draft') {
            $medications = Medication::orderBy('name')->get(['id', 'name', 'unit', 'price', 'type']);
            $labServices = Service::where('category', 'lab')->orderBy('name')->get(['id', 'name', 'price']);
            $radiologyServices = Service::where('category', 'radiology')->orderBy('name')->get(['id', 'name', 'price']);

            $catalogJson = json_encode([
                'medication' => $medications->map(function ($m) {
                    return ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit, 'price' => (float) $m->price, 'type' => $m->type];
                })->values(),
                'lab' => $labServices->map(function ($s) {
                    return ['id' => $s->id, 'name' => $s->name, 'price' => (float) $s->price];
                })->values(),
                'radiology' => $radiologyServices->map(function ($s) {
                    return ['id' => $s->id, 'name' => $s->name, 'price' => (float) $s->price];
                })->values(),
            ]);
        }

        return view('invoices.show', compact('invoice', 'catalogJson'));
    }

    public function addItem(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'item_type'  => ['required', 'in:medication,lab,radiology'],
            'itemable_id'=> ['required', 'integer', 'min:1'],
            'qty'        => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->service->addItem($invoice, $data);
            alert()->success(__('Added'), __('Item added to invoice.'));
        } catch (LogicException $e) {
            alert()->error(__('Error'), $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice);
    }

    public function removeItem(Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        try {
            $this->service->removeItem($invoice, $item);
            alert()->success(__('Removed'), __('Item removed from invoice.'));
        } catch (LogicException $e) {
            alert()->error(__('Error'), $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice);
    }

    public function finalize(Invoice $invoice): RedirectResponse
    {
        try {
            $this->service->finalize($invoice);
            alert()->success(__('Finalised'), __('Invoice has been finalised and locked.'));
        } catch (LogicException $e) {
            alert()->error(__('Error'), $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice);
    }

    public function print(Invoice $invoice): Response
    {
        $invoice->load([
            'admission.patient.insuranceCompany',
            'items.itemable.invoiceCategory',
        ]);

        $pdf = Pdf::loadView('invoices.print', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$invoice->id}.pdf");
    }
}
