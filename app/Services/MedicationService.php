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
