<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move invoice_items that belong to an 'other' category service which
        // has an invoice_category_id from section='other' → section='daily'.
        // These items should appear in the الفاتورة tab under their category group.
        DB::statement("
            UPDATE invoice_items ii
            INNER JOIN services s ON s.id = ii.itemable_id
                AND ii.itemable_type = 'App\\\\Models\\\\Service'
            SET ii.section = 'daily'
            WHERE s.category = 'other'
              AND s.invoice_category_id IS NOT NULL
              AND ii.section = 'other'
        ");
    }

    public function down(): void
    {
        // Not safely reversible without per-row history.
    }
};
