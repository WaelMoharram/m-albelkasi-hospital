<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move any existing invoice_items that belong to a supplies service
        // (section = 'daily' or 'other') into section = 'supplies'.
        DB::statement("
            UPDATE invoice_items ii
            INNER JOIN services s ON s.id = ii.itemable_id
                AND ii.itemable_type = 'App\\\\Models\\\\Service'
            SET ii.section = 'supplies'
            WHERE s.category = 'supplies'
              AND ii.section IN ('daily', 'other')
        ");
    }

    public function down(): void
    {
        // Not reversible — we can't know the original section per item.
    }
};
