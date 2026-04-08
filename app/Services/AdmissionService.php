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
    public function paginate(?string $search, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Admission::with(['patient', 'patient.insuranceCompany'])
            ->search($search)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('admission_date')
            ->paginate($perPage)
            ->withQueryString();
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

    /**
     * Discharge an admission:
     *  - Set status = discharged and discharge_date.
     *  - Delete all existing daily invoice items.
     *  - Re-seed daily items from admission_date → discharge_date (inclusive).
     */
    public function discharge(Admission $admission, string $dischargeDate): Admission
    {
        $admission->update([
            'status'         => 'discharged',
            'discharge_date' => $dischargeDate,
        ]);

        $invoice = $admission->invoice;

        if (! $invoice) {
            return $admission;
        }

        // Remove all daily items, then reseed for the finalised date range
        $invoice->items()->where('section', 'daily')->delete();

        $observer = new AdmissionObserver();
        $observer->seedDailyItems(
            invoice:       $invoice,
            admissionDate: Carbon::parse($admission->admission_date),
            endDate:       Carbon::parse($dischargeDate),
        );

        return $admission;
    }
}
