<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medication;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LogicException;

class InvoiceService
{
    public function paginate(?string $search, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::with(['admission.patient', 'admission.patient.insuranceCompany'])
            ->when($status, fn ($q) => $q->where('status', $status))
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
     * Add a manually-selected catalog item to a draft invoice.
     *
     * $data keys:
     *   item_type   → 'medication' | 'lab' | 'radiology'
     *   itemable_id → PK of Medication or Service
     *   qty         → positive integer
     *   unit_price  → decimal (pre-filled from catalog, user may override)
     */
    /**
     * Returns ['main' => InvoiceItem, 'triggered' => InvoiceItem[]]
     */
    public function addItem(Invoice $invoice, array $data): array
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot add items to a finalised invoice.');
        }

        $main      = $this->createItem($invoice, $data);
        $triggered = [];

        // Auto-add triggered services (one level deep, no chains)
        if ($data['item_type'] !== 'medication') {
            $service = Service::find((int) $data['itemable_id']);
            if ($service) {
                foreach ($service->triggers as $triggeredSvc) {
                    $triggered[] = $this->createItem($invoice, [
                        'item_type'   => $triggeredSvc->category,
                        'itemable_id' => $triggeredSvc->id,
                        'qty'         => $data['qty'],
                        'unit_price'  => $triggeredSvc->price,
                    ]);
                }
            }
        }

        $invoice->recalculateTotal();

        return ['main' => $main, 'triggered' => $triggered];
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
     * Bulk-add medications from a pasted Excel list.
     *
     * Each row: ['name' => string, 'code' => string, 'qty' => int]
     * Match priority: exact code → partial name (case-insensitive).
     *
     * Returns ['added' => [...], 'not_found' => [...], 'invoice_total' => float]
     */
    public function bulkAdd(Invoice $invoice, array $rows): array
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot add items to a finalised invoice.');
        }

        $added     = [];
        $notFound  = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $qty  = max(1, (int) ($row['qty'] ?? 1));

            $med = null;

            if ($code !== '') {
                $med = Medication::where('code', $code)->first();
            }

            if (!$med && $name !== '') {
                $med = Medication::whereRaw('TRIM(name) LIKE ?', ['%' . $name . '%'])->first();
            }

            if (!$med) {
                $notFound[] = ['code' => $code, 'name' => $name, 'qty' => $qty];
                continue;
            }

            $item = $this->createItem($invoice, [
                'item_type'   => 'medication',
                'itemable_id' => $med->id,
                'qty'         => $qty,
                'unit_price'  => (float) $med->price,
            ]);

            $added[] = [
                'id'          => $item->id,
                'name'        => $med->name,
                'unit'        => $med->unit ?? '',
                'qty'         => $item->qty,
                'unit_price'  => (float) $item->unit_price,
                'total'       => (float) $item->total,
                'section'     => $item->section,
                'update_url'  => route('invoices.items.update',  [$invoice, $item]),
                'destroy_url' => route('invoices.items.destroy', [$invoice, $item]),
            ];
        }

        if (!empty($added)) {
            $invoice->recalculateTotal();
        }

        return [
            'added'         => $added,
            'not_found'     => $notFound,
            'invoice_total' => (float) $invoice->fresh()->total_amount,
        ];
    }

    /**
     * Update unit_price for ALL invoice_items that belong to the given service
     * in this invoice (e.g. all daily charges for one service across many days).
     */
    public function updateServiceItems(Invoice $invoice, Service $service, float $unitPrice): void
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot edit items on a finalised invoice.');
        }

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

        return [$service, $expectedCategory];
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

        return [$service, 'supplies'];
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
