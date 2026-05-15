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
        $invoice->load(['admission.patient.insuranceCompany', 'items.itemable']);
        $invoice->items->loadMorph('itemable', [
            Service::class => ['invoiceCategory'],
        ]);

        // Sort all items alphabetically by their itemable name.
        $invoice->setRelation(
            'items',
            $invoice->items->sortBy(fn ($item) => $item->itemable?->name ?? '')->values()
        );

        // Pre-encode catalog as JSON for the add-item modal JS (draft only).
        // Encoding is done here — never use @json() with arrow functions in Blade,
        // as the Blade compiler misreads the closing ) inside fn($x) =>.
        $catalogJson = '{}';

        if ($invoice->status === 'draft') {
            $medications       = Medication::orderBy('name')->get(['id', 'name', 'unit', 'price', 'type', 'code']);
            $labServices       = Service::where('category', 'lab')->orderBy('name')->get(['id', 'name', 'price', 'code']);
            $radiologyServices = Service::where('category', 'radiology')->orderBy('name')->get(['id', 'name', 'price', 'code']);
            $suppliesServices  = Service::where('category', 'supplies')->orderBy('name')->get(['id', 'name', 'price', 'code']);
            $otherServices     = Service::where('category', 'other')->orderBy('name')->get(['id', 'name', 'price', 'code']);

            $toMed = fn ($m) => ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit, 'price' => (float) $m->price, 'code' => $m->code ?? ''];
            $toSvc = fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price' => (float) $s->price, 'code' => $s->code ?? ''];

            // All medications appear in both tabs; the server determines the correct
            // section (local_med / imported_med) from the medication's type field.
            $allMeds = $medications->map($toMed)->values();

            $catalogJson = json_encode([
                'local_med'    => $allMeds,
                'imported_med' => $allMeds,
                'supplies'     => $suppliesServices->map($toSvc)->values(),
                'lab'          => $labServices->map($toSvc)->values(),
                'radiology'    => $radiologyServices->map($toSvc)->values(),
                'other'        => $otherServices->map($toSvc)->values(),
            ]);
        }

        return view('invoices.show', compact('invoice', 'catalogJson'));
    }

    public function bulkAddItems(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'rows'        => ['required', 'array', 'min:1', 'max:300'],
            'rows.*.code' => ['nullable', 'string', 'max:100'],
            'rows.*.name' => ['nullable', 'string', 'max:500'],
            'rows.*.qty'  => ['required', 'integer', 'min:1'],
        ]);

        try {
            $result = $this->service->bulkAdd($invoice, $data['rows']);
            return response()->json($result);
        } catch (LogicException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function addItem(Request $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'item_type'  => ['required', 'in:medication,lab,radiology,supplies,other'],
            'itemable_id'=> ['required', 'integer', 'min:1'],
            'qty'        => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            ['main' => $item, 'triggered' => $triggeredItems] = $this->service->addItem($invoice, $data);

            if ($request->expectsJson()) {
                $item->load('itemable');
                $unit = $item->itemable instanceof Medication ? ($item->itemable->unit ?? '') : '';

                $formatItem = function (InvoiceItem $i) use ($invoice): array {
                    $i->loadMissing('itemable');
                    $unit = $i->itemable instanceof Medication ? ($i->itemable->unit ?? '') : '';
                    return [
                        'id'          => $i->id,
                        'name'        => $i->itemable->name ?? '—',
                        'unit'        => $unit,
                        'qty'         => $i->qty,
                        'unit_price'  => (float) $i->unit_price,
                        'total'       => (float) $i->total,
                        'section'     => $i->section,
                        'update_url'  => route('invoices.items.update', [$invoice, $i]),
                        'destroy_url' => route('invoices.items.destroy', [$invoice, $i]),
                    ];
                };

                return response()->json([
                    'item'            => $formatItem($item),
                    'triggered_items' => array_map($formatItem, $triggeredItems),
                    'invoice_total'   => (float) $invoice->fresh()->total_amount,
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
        $invoice->load(['admission.patient.insuranceCompany', 'items.itemable']);
        $invoice->items->loadMorph('itemable', [
            Service::class => ['invoiceCategory'],
        ]);

        $pdf = Pdf::loadView('invoices.print', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$invoice->id}.pdf");
    }
}
