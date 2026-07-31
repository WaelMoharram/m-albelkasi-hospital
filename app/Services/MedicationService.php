<?php

namespace App\Services;

use App\Models\Medication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

    /**
     * @return \Illuminate\Support\Collection<int, Medication>
     */
    public function all(?string $search, ?string $type = null): \Illuminate\Support\Collection
    {
        return Medication::query()
            ->search($search)
            ->when($type, fn($q) => $q->where('type', $type))
            ->orderBy('name')
            ->get();
    }

    public function exportSpreadsheet(?string $search, ?string $type = null): Spreadsheet
    {
        $typeLabels = ['local' => 'محلي', 'imported' => 'مستورد'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['الاسم', 'الكود', 'الوحدة', 'السعر', 'النوع', 'الكمية اليومية'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($this->all($search, $type) as $medication) {
            $sheet->fromArray([
                $medication->name,
                $medication->code,
                $medication->unit,
                (float) $medication->price,
                $typeLabels[$medication->type] ?? $medication->type,
                $medication->daily_qty,
            ], null, "A{$row}");
            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
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
