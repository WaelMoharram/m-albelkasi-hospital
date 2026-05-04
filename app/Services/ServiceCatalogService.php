<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceCatalogService
{
    public function paginate(?string $search, ?string $category = null, ?string $isDaily = null, int $perPage = 30): LengthAwarePaginator
    {
        return Service::query()
            ->search($search)
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($isDaily === '1', fn($q) => $q->where('is_daily', true))
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
