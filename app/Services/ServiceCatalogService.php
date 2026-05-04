<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceCatalogService
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Service::query()
            ->search($search)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service;
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }

    /**
     * Sync which services are auto-triggered when $service is added to an invoice.
     *
     * @param array<int> $triggerIds
     */
    public function syncTriggers(Service $service, array $triggerIds): void
    {
        $service->triggers()->sync(
            collect($triggerIds)->filter(fn ($id) => (int) $id !== $service->id)->map(fn ($id) => (int) $id)->all()
        );
    }
}
