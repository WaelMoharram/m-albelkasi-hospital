<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientService
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Patient::with('insuranceCompany')
            ->search($search)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Patient
    {
        return Patient::create($data);
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient;
    }

    public function delete(Patient $patient): void
    {
        $patient->delete();
    }
}
