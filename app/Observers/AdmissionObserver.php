<?php

namespace App\Observers;

use App\Models\Admission;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AdmissionObserver
{
    /**
     * When an admission is created:
     *  1. Create a draft Invoice.
     *  2. Auto-charge all daily services for every day from admission_date → today.
     */
    public function created(Admission $admission): void
    {
        // 1. Draft invoice
        $invoice = Invoice::create([
            'admission_id' => $admission->id,
            'invoice_date' => now()->toDateString(),
            'status'       => 'draft',
            'total_amount' => 0,
        ]);

        // 2. Seed daily service items
        $this->seedDailyItems(
            invoice:        $invoice,
            admissionDate:  Carbon::parse($admission->admission_date),
            endDate:        Carbon::today(),
        );
    }

    /**
     * Build and insert invoice_items for every (date × daily service) pair
     * in the given range, then recalculate the invoice total.
     */
    public function seedDailyItems(Invoice $invoice, Carbon $admissionDate, Carbon $endDate): void
    {
        $dailyServices = Service::where('is_daily', true)->get();

        if ($dailyServices->isEmpty()) {
            return;
        }

        $rows = [];
        $now  = now();

        /** @var Carbon $date */
        foreach (CarbonPeriod::create($admissionDate->startOfDay(), $endDate->copy()->startOfDay()) as $date) {
            foreach ($dailyServices as $service) {
                $rows[] = [
                    'invoice_id'    => $invoice->id,
                    'itemable_type' => Service::class,
                    'itemable_id'   => $service->id,
                    'qty'           => 1,
                    'unit_price'    => $service->price,
                    'total'         => $service->price,
                    'section'       => 'daily',
                    'service_date'  => $date->toDateString(),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        if (! empty($rows)) {
            InvoiceItem::insert($rows);
        }

        $invoice->recalculateTotal();
    }
}
