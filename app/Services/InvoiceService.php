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
    public function addItem(Invoice $invoice, array $data): InvoiceItem
    {
        if ($invoice->status === 'final') {
            throw new LogicException('Cannot add items to a finalised invoice.');
        }

        [$itemable, $section] = $this->resolveItemable($data['item_type'], (int) $data['itemable_id']);

        $qty       = max(1, (int) $data['qty']);
        $unitPrice = (float) $data['unit_price'];
        $total     = round($qty * $unitPrice, 2);

        $item = InvoiceItem::create([
            'invoice_id'    => $invoice->id,
            'itemable_type' => $itemable::class,
            'itemable_id'   => $itemable->id,
            'qty'           => $qty,
            'unit_price'    => $unitPrice,
            'total'         => $total,
            'section'       => $section,
            'service_date'  => null,
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
            'radiology'  => $this->resolveService($id, 'radiology'),
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
}
