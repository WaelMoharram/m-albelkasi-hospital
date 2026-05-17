<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Remove daily items seeded past the discharge date ──────
        // For each invoice whose admission is discharged, delete any
        // invoice_items that have service_date >= discharge_date.
        // (Last charged day should be discharge_date - 1.)
        DB::statement("
            DELETE ii FROM invoice_items ii
            INNER JOIN invoices inv ON inv.id = ii.invoice_id
            INNER JOIN admissions adm ON adm.id = inv.admission_id
            WHERE adm.discharge_date IS NOT NULL
              AND ii.service_date IS NOT NULL
              AND ii.service_date >= adm.discharge_date
        ");

        // ── Step 2: Seed missing is_once items into draft invoices ─────────
        $onceServices = Service::where('is_once', true)->get();

        if ($onceServices->isNotEmpty()) {
            $now = now();

            Invoice::where('status', 'draft')->with('admission')->each(function (Invoice $invoice) use ($onceServices, $now) {
                $admissionDate = $invoice->admission?->admission_date ?? $invoice->invoice_date;

                $existingIds = $invoice->items()
                    ->where('itemable_type', Service::class)
                    ->pluck('itemable_id')
                    ->toArray();

                $missing = $onceServices->whereNotIn('id', $existingIds);

                if ($missing->isEmpty()) {
                    return;
                }

                $rows = [];
                foreach ($missing as $service) {
                    $section = ($service->category === 'supplies' && ! $service->invoice_category_id)
                        ? 'supplies'
                        : 'daily';

                    $rows[] = [
                        'invoice_id'    => $invoice->id,
                        'itemable_type' => Service::class,
                        'itemable_id'   => $service->id,
                        'qty'           => 1,
                        'unit_price'    => $service->price,
                        'total'         => $service->price,
                        'section'       => $section,
                        'service_date'  => $admissionDate,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                InvoiceItem::insert($rows);
                $invoice->recalculateTotal();
            });
        }

        // ── Step 3: Recalculate totals for all affected invoices ───────────
        Invoice::where('status', 'draft')->each(fn (Invoice $inv) => $inv->recalculateTotal());
    }

    public function down(): void
    {
        // Not safely reversible.
    }
};
