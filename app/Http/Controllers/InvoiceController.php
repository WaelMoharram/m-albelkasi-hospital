<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medication;
use App\Models\Service;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
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

            $toMed = fn ($m) => ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit, 'price' => (float) $m->price];
            $toSvc = fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price' => (float) $s->price];

            $catalogJson = json_encode([
                'local_med'    => $medications->where('type', 'local')->map($toMed)->values(),
                'imported_med' => $medications->where('type', 'imported')->map($toMed)->values(),
                'lab'          => $labServices->map($toSvc)->values(),
                'radiology'    => $radiologyServices->map($toSvc)->values(),
            ]);
        }

        return view('invoices.show', compact('invoice', 'catalogJson'));
    }

    public function addItem(Request $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'item_type'  => ['required', 'in:medication,lab,radiology'],
            'itemable_id'=> ['required', 'integer', 'min:1'],
            'qty'        => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $item = $this->service->addItem($invoice, $data);

            if ($request->expectsJson()) {
                $item->load('itemable');
                $unit = $item->itemable instanceof Medication ? ($item->itemable->unit ?? '') : '';
                return response()->json([
                    'item' => [
                        'id'          => $item->id,
                        'name'        => $item->itemable->name ?? '—',
                        'unit'        => $unit,
                        'qty'         => $item->qty,
                        'unit_price'  => (float) $item->unit_price,
                        'total'       => (float) $item->total,
                        'section'     => $item->section,
                        'update_url'  => route('invoices.items.update', [$invoice, $item]),
                        'destroy_url' => route('invoices.items.destroy', [$invoice, $item]),
                    ],
                    'invoice_total' => (float) $invoice->fresh()->total_amount,
                ]);
            }

            alert()->success(__('Added'), __('Item added to invoice.'));
        } catch (LogicException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            alert()->error(__('Error'), $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        Gate::authorize(Permission::DeleteInvoices->value);

        $this->service->delete($invoice);

        alert()->success(__('Deleted'), __('Invoice deleted successfully.'));

        return redirect()->route('invoices.index');
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        $data = $request->validate([
            'qty'        => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->service->updateItem($invoice, $item, $data);
            alert()->success(__('Updated'), __('Item updated successfully.'));
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
