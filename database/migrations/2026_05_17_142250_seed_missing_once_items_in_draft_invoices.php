<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $onceServices = Service::where('is_once', true)->get();

        if ($onceServices->isEmpty()) {
            return;
        }

        $now = now();

        Invoice::where('status', 'draft')->with('admission')->each(function (Invoice $invoice) use ($onceServices, $now) {
            $admissionDate = $invoice->admission?->admission_date ?? $invoice->invoice_date;

            // Find once-services not yet present in this invoice
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
                $section = ($service->category === 'supplies' && ! $service->invoice_category_id) ? 'supplies' : 'daily';

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

    public function down(): void
    {
        // Not safely reversible.
    }
};
