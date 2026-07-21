<?php

namespace App\Services;

use App\Models\Medication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MedicationService
{
    public function paginate(?string $search, ?string $type = null, int $perPage = 30): LengthAwarePaginator
    {
        return Medication::query()
            ->with('triggeredServices')
            ->search($search)
            ->when($type, fn($q) => $q->where('type', $type))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Medication
    {
        return Medication::create($data);
    }

    public function update(Medication $medication, array $data): Medication
    {
        $medication->update($data);

        return $medication;
    }

    public function delete(Medication $medication): void
    {
        $medication->delete();
    }

    /**
     * Group medications that share the same non-empty HIO code — a data
     * entry error that has caused billing/catalog duplicates before (a bulk
     * price-sheet import can create a second row for an item that already
     * exists under a slightly different name).
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Medication>>
     */
    public function duplicateCodeGroups(): \Illuminate\Support\Collection
    {
        return Medication::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('name')
            ->get()
            ->groupBy('code')
            ->filter(fn ($group) => $group->count() > 1);
    }

    /**
     * Sync which services are auto-triggered when this medication is added to an invoice.
     *
     * @param array<int> $serviceIds
     */
    public function syncTriggers(Medication $medication, array $serviceIds): void
    {
        $medication->triggeredServices()->sync(
            collect($serviceIds)->map(fn ($id) => (int) $id)->filter()->all()
        );
    }
}
