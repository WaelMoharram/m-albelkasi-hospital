<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoice_items MODIFY COLUMN section ENUM('local_med','imported_med','supplies','lab','radiology','daily','other') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invoice_items MODIFY COLUMN section ENUM('local_med','imported_med','lab','radiology','daily','other') NOT NULL");
    }
};
