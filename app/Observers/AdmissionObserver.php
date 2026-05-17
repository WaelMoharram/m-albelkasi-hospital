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

        // 2. Seed once-per-admission items
        $this->seedOnceItems($invoice, $admission->admission_date);

        // 3. Seed daily service items — up to yesterday OR discharge_date-1,
        //    whichever comes first (so retroactively-entered discharges don't
        //    bleed past the actual discharge day).
        $endDate = Carbon::yesterday();
        if ($admission->discharge_date) {
            $lastChargedDay = Carbon::parse($admission->discharge_date)->subDay();
            if ($lastChargedDay->lt($endDate)) {
                $endDate = $lastChargedDay;
            }
        }

        $this->seedDailyItems(
            invoice:       $invoice,
            admissionDate: Carbon::parse($admission->admission_date),
            endDate:       $endDate,
        );
    }

    /**
     * Insert one invoice_item per is_once service, dated on the admission date.
     */
    public function seedOnceItems(Invoice $invoice, string $admissionDate): void
    {
        $onceServices = Service::where('is_once', true)->get();

        if ($onceServices->isEmpty()) {
            return;
        }

        $now  = now();
        $rows = [];

        foreach ($onceServices as $service) {
            $rows[] = [
                'invoice_id'    => $invoice->id,
                'itemable_type' => Service::class,
                'itemable_id'   => $service->id,
                'qty'           => 1,
                'unit_price'    => $service->price,
                'total'         => $service->price,
                'section'       => ($service->category === 'supplies' && ! $service->invoice_category_id) ? 'supplies' : 'daily',
                'service_date'  => $admissionDate,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        InvoiceItem::insert($rows);
        $invoice->recalculateTotal();
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
                $qty    = max(1, $service->daily_qty ?? 1);
                $rows[] = [
                    'invoice_id'    => $invoice->id,
                    'itemable_type' => Service::class,
                    'itemable_id'   => $service->id,
                    'qty'           => $qty,
                    'unit_price'    => $service->price,
                    'total'         => $service->price * $qty,
                    'section'       => ($service->category === 'supplies' && ! $service->invoice_category_id) ? 'supplies' : 'daily',
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
