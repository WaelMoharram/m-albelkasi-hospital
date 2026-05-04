<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN category ENUM('supplies','lab','radiology','other') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE services SET category='supplies' WHERE category='other'");
        DB::statement("ALTER TABLE services MODIFY COLUMN category ENUM('supplies','lab','radiology') NOT NULL");
    }
};
