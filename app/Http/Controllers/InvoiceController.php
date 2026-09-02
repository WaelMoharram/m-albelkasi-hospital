<?php

namespace App\Http\Controllers;

use App\Enums\MedicalReportType;
use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medication;
use App\Models\Service;
use App\Services\InvoiceService;
use App\Services\MedicalReportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use LogicException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $service,
        private readonly ReportService $reportService,
        private readonly MedicalReportService $medicalReportService,
    ) {}

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
            $medications = Medication::orderBy('name')->get(['id', 'name', 'unit', 'price', 'type', 'code']);
            $labServices = Service::where('category', 'lab')->orderBy('name')->get(['id', 'name', 'price', 'code']);
            $radiologyServices = Service::where('category', 'radiology')->orderBy('name')->get(['id', 'name', 'price', 'code']);
            $suppliesServices = Service::where('category', 'supplies')->orderBy('name')->get(['id', 'name', 'price', 'code']);
            $otherServices = Service::where('category', 'other')->orderBy('name')->get(['id', 'name', 'price', 'code']);

            $toMed = fn ($m) => ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit, 'price' => (float) $m->price, 'code' => $m->code ?? ''];
            $toSvc = fn ($s) => ['id' => $s->id, 'name' => $s->name, 'price' => (float) $s->price, 'code' => $s->code ?? ''];

            // All medications appear in both tabs; the server determines the correct
            // section (local_med / imported_med) from the medication's type field.
            $allMeds = $medications->map($toMed)->values();

            $catalogJson = json_encode([
                'local_med' => $allMeds,
                'imported_med' => $allMeds,
                'supplies' => $suppliesServices->map($toSvc)->values(),
                'lab' => $labServices->map($toSvc)->values(),
                'radiology' => $radiologyServices->map($toSvc)->values(),
                'other' => $otherServices->map($toSvc)->values(),
            ]);
        }

        $admissionIndicators = $this->service->admissionIndicators($invoice);
        $indicators = $this->reportService->getLiveBedAvailability();

        $inpatientReports = $this->medicalReportService->forAdmission($invoice->admission, MedicalReportType::Inpatient);
        $radiologyReports = $this->medicalReportService->forAdmission($invoice->admission, MedicalReportType::Radiology);

        return view('invoices.show', compact(
            'invoice', 'catalogJson', 'admissionIndicators', 'indicators',
            'inpatientReports', 'radiologyReports'
        ));
    }

    public function bulkImportExample(): StreamedResponse
    {
        $writer = new Xlsx($this->service->bulkImportExampleSpreadsheet());

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'invoice-bulk-import-example.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function bulkAddItems(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $result = $this->service->bulkAddFromFile($invoice, $data['file']);

            return response()->json($result);
        } catch (LogicException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function addItem(Request $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'item_type' => ['required', 'in:medication,lab,radiology,supplies,other'],
            'itemable_id' => ['required', 'integer', 'min:1'],
            'qty' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $result = $this->service->addItem($invoice, $data);
            $item = $result['main'];
            $itemMerged = $result['main_merged'];
            $itemDelta = $result['main_delta'];
            $triggeredItems = $result['triggered'];
            $triggeredMerged = $result['triggered_merged'];
            $triggeredDelta = $result['triggered_delta'];

            if ($request->expectsJson()) {
                $formatItem = function (InvoiceItem $i, bool $merged, float $delta) use ($invoice): array {
                    $i->loadMissing('itemable');
                    $unit = $i->itemable instanceof Medication ? ($i->itemable->unit ?? '') : '';
                    $categoryName = '';
                    $categoryId = null;
                    if ($i->section === 'daily' && $i->itemable instanceof Service) {
                        $i->itemable->loadMissing('invoiceCategory');
                        $categoryName = $i->itemable->invoiceCategory?->name ?? '';
                        $categoryId = $i->itemable->invoice_category_id;
                    }

                    return [
                        'id' => $i->id,
                        'name' => $i->itemable->name ?? '—',
                        'unit' => $unit,
                        'qty' => $i->qty,
                        'unit_price' => (float) $i->unit_price,
                        'total' => (float) $i->total,
                        'section' => $i->section,
                        'category_name' => $categoryName,
                        'category_id' => $categoryId,
                        'merged' => $merged,
                        'delta_total' => $delta,
                        'update_url' => route('invoices.items.update', [$invoice, $i]),
                        'destroy_url' => route('invoices.items.destroy', [$invoice, $i]),
                    ];
                };

                $triggeredFormatted = [];
                foreach ($triggeredItems as $idx => $triggeredItem) {
                    $triggeredFormatted[] = $formatItem($triggeredItem, $triggeredMerged[$idx], $triggeredDelta[$idx]);
                }

                return response()->json([
                    'item' => $formatItem($item, $itemMerged, $itemDelta),
                    'triggered_items' => $triggeredFormatted,
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

    public function updateServiceItems(Request $request, Invoice $invoice, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->service->updateServiceItems($invoice, $service, (float) $data['unit_price'], (int) $data['qty']);
            alert()->success(__('Updated'), __('Items updated successfully.'));
        } catch (LogicException $e) {
            alert()->error(__('Error'), $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice);
    }

    public function removeServiceItems(Invoice $invoice, Service $service): RedirectResponse
    {
        try {
            $this->service->removeServiceItems($invoice, $service);
            alert()->success(__('Removed'), __('Item removed from invoice.'));
        } catch (LogicException $e) {
            alert()->error(__('Error'), $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice);
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
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

    public function bulkRemoveItems(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'item_ids' => ['array'],
            'item_ids.*' => ['integer'],
            'service_ids' => ['array'],
            'service_ids.*' => ['integer'],
        ]);

        try {
            $this->service->bulkRemove($invoice, $data['item_ids'] ?? [], $data['service_ids'] ?? []);

            return response()->json(['invoice_total' => (float) $invoice->fresh()->total_amount]);
        } catch (LogicException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
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

    public function print(Invoice $invoice): View
    {
        $invoice->load(['admission.patient.insuranceCompany', 'items.itemable']);
        $invoice->items->loadMorph('itemable', [
            Service::class => ['invoiceCategory'],
        ]);

        $bedIndicators = $this->reportService->getLiveBedAvailability();

        return view('invoices.print', compact('invoice', 'bedIndicators'));
    }
}
