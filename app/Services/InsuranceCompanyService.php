<?php

namespace App\Services;

use App\Models\InsuranceCompany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InsuranceCompanyService
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return InsuranceCompany::query()
            ->search($search)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): InsuranceCompany
    {
        return InsuranceCompany::create($data);
    }

    public function update(InsuranceCompany $company, array $data): InsuranceCompany
    {
        $company->update($data);

        return $company;
    }

    public function delete(InsuranceCompany $company): void
    {
        $company->delete();
    }
}
