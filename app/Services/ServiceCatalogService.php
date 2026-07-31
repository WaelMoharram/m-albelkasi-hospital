<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

    /**
     * @return \Illuminate\Support\Collection<int, Service>
     */
    public function all(?string $search, ?string $category = null, ?string $isDaily = null, ?string $isOnce = null): \Illuminate\Support\Collection
    {
        return Service::query()
            ->search($search)
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($isDaily === '1', fn($q) => $q->where('is_daily', true))
            ->when($isOnce === '1', fn($q) => $q->where('is_once', true))
            ->orderBy('name')
            ->get();
    }

    public function exportSpreadsheet(?string $search, ?string $category = null, ?string $isDaily = null, ?string $isOnce = null): Spreadsheet
    {
        $categoryLabels = [
            'supplies'  => __('Supplies'),
            'lab'       => __('Lab'),
            'radiology' => __('Radiology'),
            'other'     => __('Other'),
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['الاسم', 'الكود', 'السعر', 'الفئة', 'يومي', 'الكمية اليومية', 'مرة واحدة'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($this->all($search, $category, $isDaily, $isOnce) as $service) {
            $sheet->fromArray([
                $service->name,
                $service->code,
                (float) $service->price,
                $categoryLabels[$service->category] ?? $service->category,
                $service->is_daily ? 'نعم' : 'لا',
                $service->daily_qty,
                $service->is_once ? 'نعم' : 'لا',
            ], null, "A{$row}");
            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
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
