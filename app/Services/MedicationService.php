<?php

namespace App\Services;

use App\Models\Medication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MedicationService
{
    public function paginate(?string $search, ?string $type = null, int $perPage = 15): LengthAwarePaginator
    {
        return Medication::query()
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
}
