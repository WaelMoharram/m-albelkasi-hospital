<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Observers\AdmissionObserver;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdmissionService
{
    public function paginate(?string $search, ?string $status, ?string $from = null, ?string $to = null, bool $all = false, int $perPage = 15): LengthAwarePaginator
    {
        $query = Admission::with(['patient', 'patient.insuranceCompany'])
            ->search($search)
            ->when($status, fn ($q) => $q->where('status', $status));

        if (! $all) {
            [$start, $end] = $this->resolveDateRange($from, $to);
            $query->whereBetween('admission_date', [$start, $end]);
        }

        return $query
            ->orderByDesc('admission_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Default admissions/invoices list period: last month and the month before it.
     * The current (in-progress) month is excluded until it becomes "last month" —
     * invoices are only sent out the month after they close.
     */
    public function defaultPeriod(): array
    {
        return [now()->subMonths(2)->format('Y-m'), now()->subMonth()->format('Y-m')];
    }

    private function resolveDateRange(?string $from, ?string $to): array
    {
        [$defaultFrom, $defaultTo] = $this->defaultPeriod();

        $start = Carbon::parse(($from ?: $defaultFrom) . '-01')->startOfMonth();
        $end   = Carbon::parse(($to ?: $defaultTo) . '-01')->endOfMonth();

        return [$start, $end];
    }

    /**
     * Create a new admission.
     * The AdmissionObserver@created will fire automatically and seed daily items.
     */
    public function create(array $data): Admission
    {
        return Admission::create($data);
    }

    public function update(Admission $admission, array $data): Admission
    {
        $admission->update($data);

        return $admission;
    }

    public function delete(Admission $admission): void
    {
        if ($admission->invoice) {
            $admission->invoice->items()->delete();
            $admission->invoice->delete();
        }

        $admission->delete();
    }

    /**
     * Discharge an admission:
     *  - Set status = discharged and discharge_date.
     *  - Delete all existing daily invoice items.
     *  - Re-seed daily items from admission_date → discharge_date (inclusive).
     */
    public function discharge(Admission $admission, string $dischargeDate, string $dischargeReason = 'discharged'): Admission
    {
        $admission->update([
            'status'           => 'discharged',
            'discharge_date'   => $dischargeDate,
            'discharge_reason' => $dischargeReason,
        ]);

        $invoice = $admission->invoice;

        if (! $invoice) {
            return $admission;
        }

        // Remove only is_daily service items (per-day recurring charges) then
        // re-seed for the finalised date range.
        // Daily supply services are stored under section = 'supplies' (see
        // AdmissionObserver::seedDailyItems), not 'daily', so both must be
        // cleared or discharge leaves stale supply rows and duplicates them.
        // is_once items (e.g. radiology, one-time fees) must NOT be deleted —
        // they are charged once per admission regardless of discharge date.
        $invoice->items()
            ->whereIn('section', ['daily', 'supplies'])
            ->whereHasMorph('itemable', [\App\Models\Service::class], fn ($q) => $q->where('is_daily', true))
            ->delete();

        $observer = new AdmissionObserver();
        // Discharge day is not billed; bill admission_date up to the day before discharge.
        $endDate = Carbon::parse($dischargeDate)->subDay();
        if ($endDate->lt(Carbon::parse($admission->admission_date))) {
            $endDate = Carbon::parse($admission->admission_date);
        }

        $observer->seedDailyItems(
            invoice:       $invoice,
            admissionDate: Carbon::parse($admission->admission_date),
            endDate:       $endDate,
        );

        return $admission;
    }
}
