<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceCatalogService
{
    public function paginate(?string $search, ?string $category = null, ?string $isDaily = null, ?string $isOnce = null, int $perPage = 30): LengthAwarePaginator
    {
        return Service::query()
            ->with('triggers')
            ->search($search)
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($isDaily === '1', fn($q) => $q->where('is_daily', true))
            ->when($isOnce === '1', fn($q) => $q->where('is_once', true))
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
     * Group services that share the same non-empty HIO code. See
     * MedicationService::duplicateCodeGroups() for why this happens.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Service>>
     */
    public function duplicateCodeGroups(): \Illuminate\Support\Collection
    {
        return Service::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('name')
            ->get()
            ->groupBy('code')
            ->filter(fn ($group) => $group->count() > 1);
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
