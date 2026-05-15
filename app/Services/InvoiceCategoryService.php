<?php

namespace App\Services;

use App\Models\InvoiceCategory;
use Illuminate\Database\Eloquent\Collection;

class InvoiceCategoryService
{
    public function all(): Collection
    {
        return InvoiceCategory::ordered()->with('services.triggers')->get();
    }

    public function create(array $data): InvoiceCategory
    {
        return InvoiceCategory::create($data);
    }

    public function update(InvoiceCategory $category, array $data): InvoiceCategory
    {
        $category->update($data);
        return $category;
    }

    public function delete(InvoiceCategory $category): void
    {
        $category->delete();
    }
}
