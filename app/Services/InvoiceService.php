<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medication;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class InvoiceService
{
    /**
     * With no explicit status filter, draft invoices always show (they're
     * still open work), while finalized ones only stay visible through the
     * end of the month they were finalized in, then drop out of the default
     * list. Selecting a status explicitly (e.g. "final") bypasses this and
     * shows every matching invoice regardless of age.
     */
    public function paginate(?string $search, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::with(['admission.patient', 'admission.patient.insuranceCompany'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when(!$status, fn ($q) => $q->where(function ($q) {
                $q->where('status', 'draft')
                  ->orWhere(function ($q) {
                      $q->where('status', 'final')
                        ->whereYear('updated_at', now()->year)
                        ->whereMonth('updated_at', now()->month);
                  });
            }))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('admission.patient', fn ($p) =>
                    $p->where('name', 'like', "%{$search}%")
                      ->orWhere('national_id', 'like', "%{$search}%")
                );
            })
            ->orderByDesc('invoice_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Per-invoice indicators: age is computed from the patient's DOB (never
     * entered manually), days is admission_date → discharge_date (or → today
     * while still active), and cost per day is the invoice's grand total
     * divided by those days.
     */
    public function admissionIndicators(Invoice $invoice): array
    {
        $admission = $invoice->admission;
        $days = max(1, (int) $admission->admission_date->diffInDays($admission->discharge_date ?? now()));
        $age  = $admission->patient->dob
            ? (int) $admission->patient->dob->diffInYears($admission->admission_date)
            : null;

        return [
            'age'          => $age,
            'days'         => $days,
            'cost_per_day' => round((float) $invoice->total_amount / $days, 2),
        ];
    }

    /**
     * Add a manually-selected catalog item to a draft invoice.
     *
     * $data keys:
     *   item_type   → 'medication' | 'lab' | 'radiology'
     *   itemable_id → PK of Medication or Service
     *   qty         → positive integer
     *   unit_price  → decimal (pre-filled from catalog, user may override)
     */
    /**
     * Returns ['main' => InvoiceItem, 'main_merged' => bool, 'main_delta' => float,
     *          'triggered' => InvoiceItem[], 'triggered_merged' => bool[], 'triggered_delta' => float[]]
     */
    public function addItem(Invoice $invoice, array $data): array
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot add items to a finalised invoice.');
        }

        [$main, $mainMerged, $mainDelta] = $this->createOrMergeItem($invoice, $data);
        $triggered       = [];
        $triggeredMerged = [];
        $triggeredDelta  = [];

        // Auto-add triggered services (one level deep, no chains)
        if ($data['item_type'] !== 'medication') {
            $service = Service::find((int) $data['itemable_id']);
            if ($service) {
                foreach ($service->triggers as $triggeredSvc) {
                    [$item, $merged, $delta] = $this->createOrMergeItem($invoice, [
                        'item_type'   => $triggeredSvc->category,
                        'itemable_id' => $triggeredSvc->id,
                        'qty'         => $data['qty'],
                        'unit_price'  => $triggeredSvc->price,
                    ]);
                    $triggered[]       = $item;
                    $triggeredMerged[] = $merged;
                    $triggeredDelta[]  = $delta;
                }
            }
        }

        $invoice->recalculateTotal();

        return [
            'main'             => $main,
            'main_merged'      => $mainMerged,
            'main_delta'       => $mainDelta,
            'triggered'        => $triggered,
            'triggered_merged' => $triggeredMerged,
            'triggered_delta'  => $triggeredDelta,
        ];
    }

    /**
     * Add a manually-selected item to a draft invoice, merging into an existing
     * row for the same itemable instead of creating a duplicate — mirrors
     * bulkAdd()'s behaviour. Only merges into manually-added rows (service_date
     * null) so auto-charged daily rows for the same service are never touched.
     *
     * Returns [InvoiceItem, bool $wasMerged, float $deltaTotal]
     */
    private function createOrMergeItem(Invoice $invoice, array $data): array
    {
        [$itemable, ] = $this->resolveItemable($data['item_type'], (int) $data['itemable_id']);

        $existing = $invoice->items()
            ->where('itemable_type', $itemable::class)
            ->where('itemable_id', $itemable->id)
            ->whereNull('service_date')
            ->first();

        if ($existing) {
            $qty      = max(1, (int) $data['qty']);
            $oldTotal = (float) $existing->total;
            $existing->qty  += $qty;
            $existing->total = round($existing->qty * (float) $existing->unit_price, 2);
            $existing->save();

            return [$existing, true, (float) $existing->total - $oldTotal];
        }

        $item = $this->createItem($invoice, $data);

        return [$item, false, (float) $item->total];
    }

    private function createItem(Invoice $invoice, array $data): InvoiceItem
    {
        [$itemable, $section] = $this->resolveItemable($data['item_type'], (int) $data['itemable_id']);

        $qty       = max(1, (int) $data['qty']);
        $unitPrice = (float) $data['unit_price'];
        $total     = round($qty * $unitPrice, 2);

        return InvoiceItem::create([
            'invoice_id'    => $invoice->id,
            'itemable_type' => $itemable::class,
            'itemable_id'   => $itemable->id,
            'qty'           => $qty,
            'unit_price'    => $unitPrice,
            'total'         => $total,
            'section'       => $section,
            'service_date'  => null,
        ]);
    }

    /**
     * Parse an uploaded Excel/CSV sheet (columns: Name, Qty, Code) and bulk-add
     * the matched medications/supplies via bulkAdd().
     *
     * A row is only treated as data if its Qty column is numeric — this
     * transparently skips a header row and any blank lines.
     */
    public function bulkAddFromFile(Invoice $invoice, UploadedFile $file): array
    {
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();

        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getFormattedValue());
            }
            [$name, $qty, $code] = array_pad($cells, 3, '');

            if (! is_numeric($qty)) {
                continue;
            }

            $name = trim($name);
            $code = trim($code);

            if ($name === '' && $code === '') {
                continue;
            }

            $rows[] = ['name' => $name, 'code' => $code, 'qty' => max(1, (int) round((float) $qty))];
        }

        if (empty($rows)) {
            throw new LogicException('No valid rows found in the uploaded file.');
        }

        return $this->bulkAdd($invoice, $rows);
    }

    /**
     * Build the downloadable example sheet for the bulk-import panel — same
     * column order bulkAddFromFile() expects (Name, Qty, Code). Uses two real
     * catalog rows (one medication, one supply) so the example matches
     * successfully if uploaded as-is.
     */
    public function bulkImportExampleSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(['اسم الصنف', 'الكمية', 'كود الصنف'], null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $row = 2;
        $exampleMedication = Medication::whereNotNull('code')->orderBy('name')->first();
        if ($exampleMedication) {
            $sheet->fromArray([$exampleMedication->name, 2, $exampleMedication->code], null, "A{$row}");
            $row++;
        }
        $exampleSupply = Service::where('category', 'supplies')->whereNotNull('code')->orderBy('name')->first();
        if ($exampleSupply) {
            $sheet->fromArray([$exampleSupply->name, 1, $exampleSupply->code], null, "A{$row}");
            $row++;
        }

        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Bulk-add medications and services from parsed Excel rows.
     *
     * Each row: ['name' => string, 'code' => string, 'qty' => int]
     * Match priority: exact code → partial name (case-insensitive), medications first,
     * then services by category (supplies, lab, radiology, other).
     *
     * Returns ['added' => [...], 'not_found' => [...], 'invoice_total' => float]
     */
    public function bulkAdd(Invoice $invoice, array $rows): array
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot add items to a finalised invoice.');
        }

        $added     = [];
        $updated   = [];
        $notFound  = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $qty  = max(1, (int) ($row['qty'] ?? 1));

            $itemType = 'medication';
            $match    = null;

            if ($code !== '') {
                $match = Medication::where('code', $code)->first();
            }
            if (!$match && $name !== '') {
                $match = Medication::whereRaw('TRIM(name) LIKE ?', ['%' . $name . '%'])->first();
            }

            if (!$match) {
                foreach (['supplies', 'lab', 'radiology', 'other'] as $category) {
                    if ($code !== '') {
                        $match = Service::where('category', $category)->where('code', $code)->first();
                    }
                    if (!$match && $name !== '') {
                        $match = Service::where('category', $category)->whereRaw('TRIM(name) LIKE ?', ['%' . $name . '%'])->first();
                    }
                    if ($match) {
                        $itemType = $category;
                        break;
                    }
                }
            }

            if (!$match) {
                $notFound[] = ['code' => $code, 'name' => $name, 'qty' => $qty];
                continue;
            }

            // If this item already has a line in the invoice, add to it.
            $existing = $invoice->items()
                ->where('itemable_type', $match::class)
                ->where('itemable_id', $match->id)
                ->first();

            if ($existing) {
                $oldTotal      = (float) $existing->total;
                $existing->qty += $qty;
                $existing->total = round($existing->qty * (float) $existing->unit_price, 2);
                $existing->save();

                $updated[] = [
                    'id'          => $existing->id,
                    'name'        => $match->name,
                    'qty'         => $existing->qty,
                    'unit_price'  => (float) $existing->unit_price,
                    'total'       => (float) $existing->total,
                    'delta_total' => (float) $existing->total - $oldTotal,
                    'section'     => $existing->section,
                ];
            } else {
                $item = $this->createItem($invoice, [
                    'item_type'   => $itemType,
                    'itemable_id' => $match->id,
                    'qty'         => $qty,
                    'unit_price'  => (float) $match->price,
                ]);

                $added[] = [
                    'id'          => $item->id,
                    'name'        => $match->name,
                    'unit'        => $match->unit ?? '',
                    'qty'         => $item->qty,
                    'unit_price'  => (float) $item->unit_price,
                    'total'       => (float) $item->total,
                    'section'     => $item->section,
                    'update_url'  => route('invoices.items.update',  [$invoice, $item]),
                    'destroy_url' => route('invoices.items.destroy', [$invoice, $item]),
                ];
            }
        }

        if (!empty($added) || !empty($updated)) {
            $invoice->recalculateTotal();
        }

        return [
            'added'         => $added,
            'updated'       => $updated,
            'not_found'     => $notFound,
            'invoice_total' => (float) $invoice->fresh()->total_amount,
        ];
    }

    /**
     * Update unit_price for ALL invoice_items that belong to the given service
     * in this invoice (e.g. all daily charges for one service across many days).
     */
    public function updateServiceItems(Invoice $invoice, Service $service, float $unitPrice, int $newTotalQty = 0): void
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot edit items on a finalised invoice.');
        }

        $items = $invoice->items()
            ->where('itemable_type', Service::class)
            ->where('itemable_id', $service->id)
            ->orderBy('service_date')
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        // ── Adjust total qty (for multi-record / daily services) ──────────
        if ($newTotalQty > 0) {
            $currentTotal = (int) $items->sum('qty');

            if ($newTotalQty < $currentTotal) {
                // Delete records from the end; partially reduce last survivor if needed
                $toRemove = $currentTotal - $newTotalQty;
                foreach ($items->reverse()->values() as $item) {
                    if ($toRemove <= 0) break;
                    if ($item->qty <= $toRemove) {
                        $toRemove -= $item->qty;
                        $item->delete();
                    } else {
                        $item->qty -= $toRemove;
                        $item->save();
                        $toRemove = 0;
                    }
                }
            } elseif ($newTotalQty > $currentTotal) {
                // Add the difference to the last record
                $last       = $items->last();
                $last->qty += ($newTotalQty - $currentTotal);
                $last->save();
            }
        }

        // ── Update unit_price and recalculate total on all surviving records ─
        $invoice->items()
            ->where('itemable_type', Service::class)
            ->where('itemable_id', $service->id)
            ->each(function (InvoiceItem $item) use ($unitPrice) {
                $item->update([
                    'unit_price' => $unitPrice,
                    'total'      => round($item->qty * $unitPrice, 2),
                ]);
            });

        $invoice->recalculateTotal();
    }

    /**
     * Remove ALL invoice_items for the given service from an invoice — including
     * daily auto-charged records across every day. Used to fully remove an
     * aggregated (multi-record) item that updateServiceItems() can only edit
     * down to, never delete (its qty floor is 1).
     */
    public function removeServiceItems(Invoice $invoice, Service $service): void
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot remove items from a finalised invoice.');
        }

        $invoice->items()
            ->where('itemable_type', Service::class)
            ->where('itemable_id', $service->id)
            ->delete();

        $invoice->recalculateTotal();
    }

    /**
     * Remove a batch of invoice items in one go — used by the tab checkboxes'
     * "select all + delete" control. $itemIds are single invoice_item rows;
     * $serviceIds remove ALL records for that service (aggregated/multi-day
     * items), same as removeServiceItems().
     */
    public function bulkRemove(Invoice $invoice, array $itemIds, array $serviceIds): void
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot remove items from a finalised invoice.');
        }

        if (!empty($itemIds)) {
            $invoice->items()->whereIn('id', $itemIds)->delete();
        }

        if (!empty($serviceIds)) {
            $invoice->items()
                ->where('itemable_type', Service::class)
                ->whereIn('itemable_id', $serviceIds)
                ->delete();
        }

        $invoice->recalculateTotal();
    }

    /**
     * Delete an invoice and all its items.
     */
    public function delete(Invoice $invoice): void
    {
        $invoice->items()->delete();
        $invoice->delete();
    }

    /**
     * Update qty / unit_price of an existing draft invoice item.
     */
    public function updateItem(Invoice $invoice, InvoiceItem $item, array $data): InvoiceItem
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot edit items on a finalised invoice.');
        }

        $qty       = max(1, (int) $data['qty']);
        $unitPrice = (float) $data['unit_price'];

        $item->update([
            'qty'        => $qty,
            'unit_price' => $unitPrice,
            'total'      => round($qty * $unitPrice, 2),
        ]);

        $invoice->recalculateTotal();

        return $item;
    }

    /**
     * Remove an item from a draft invoice.
     */
    public function removeItem(Invoice $invoice, InvoiceItem $item): void
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot remove items from a finalised invoice.');
        }

        $item->delete();
        $invoice->recalculateTotal();
    }

    /**
     * Lock the invoice — transitions draft → final.
     */
    public function finalize(Invoice $invoice): Invoice
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Invoice is already finalised.');
        }

        $invoice->update(['status' => 'final']);

        return $invoice;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Resolve the Eloquent model instance and the denormalised section string.
     *
     * item_type   model       section derivation
     * ----------  ----------  ----------------------------------------
     * medication  Medication  'local_med' | 'imported_med'  (from type)
     * lab         Service     'lab'       (category must be 'lab')
     * radiology   Service     'radiology' (category must be 'radiology')
     */
    private function resolveItemable(string $itemType, int $id): array
    {
        return match ($itemType) {
            'medication' => $this->resolveMedication($id),
            'lab'        => $this->resolveService($id, 'lab'),
            'radiology'  => $this->resolveRadiologyService($id),
            'supplies'   => $this->resolveSuppliesService($id),
            'other'      => $this->resolveOtherService($id),
            default      => throw new \InvalidArgumentException("Unknown item type: {$itemType}"),
        };
    }

    private function resolveMedication(int $id): array
    {
        $med = Medication::findOrFail($id);
        $section = $med->type === 'local' ? 'local_med' : 'imported_med';

        return [$med, $section];
    }

    private function resolveService(int $id, string $expectedCategory): array
    {
        $service = Service::findOrFail($id);

        if ($service->category !== $expectedCategory) {
            throw new \InvalidArgumentException(
                "Service #{$id} is not a {$expectedCategory} service."
            );
        }

        // Any service with an invoice category appears in الفاتورة tab.
        $section = $service->invoice_category_id ? 'daily' : $expectedCategory;

        return [$service, $section];
    }

    private function resolveRadiologyService(int $id): array
    {
        $service = Service::findOrFail($id);

        if ($service->category !== 'radiology') {
            throw new \InvalidArgumentException(
                "Service #{$id} is not a radiology service."
            );
        }

        // Route to الفاتورة tab when the service belongs to an invoice category;
        // otherwise fall back to the legacy 'radiology' section.
        $section = $service->invoice_category_id ? 'daily' : 'radiology';

        return [$service, $section];
    }

    private function resolveSuppliesService(int $id): array
    {
        $service = Service::findOrFail($id);

        if ($service->category !== 'supplies') {
            throw new \InvalidArgumentException(
                "Service #{$id} is not a supplies service."
            );
        }

        // Any service with an invoice category appears in الفاتورة tab.
        $section = $service->invoice_category_id ? 'daily' : 'supplies';

        return [$service, $section];
    }

    private function resolveOtherService(int $id): array
    {
        $service = Service::findOrFail($id);

        if ($service->category !== 'other') {
            throw new \InvalidArgumentException(
                "Service #{$id} is not an other service."
            );
        }

        // Route to daily tab if the service belongs to an invoice category;
        // otherwise land in the "other" (أخرى) group.
        $section = $service->invoice_category_id ? 'daily' : 'other';

        return [$service, $section];
    }
}
