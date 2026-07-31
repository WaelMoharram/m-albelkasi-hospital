<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The previous migration (seed_default_medication_discounts) only
     * recalculated draft invoices, to avoid touching finalized ones by
     * default. Finalized invoices created before the discount settings
     * existed still carry the old, pre-discount total_amount — recalculating
     * here isn't an item mutation (that's what InvoiceService::addItem /
     * removeItem guard against), just correcting a stored total.
     */
    public function up(): void
    {
        Invoice::where('status', 'final')->each(fn (Invoice $invoice) => $invoice->recalculateTotal());
    }

    public function down(): void
    {
        // Not safely reversible.
    }
};
