<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN category ENUM('daily','lab','radiology','supplies') NOT NULL");
        DB::statement("UPDATE services SET category='supplies' WHERE category='daily'");
    }

    public function down(): void
    {
        DB::statement("UPDATE services SET category='daily' WHERE category='supplies'");
        DB::statement("ALTER TABLE services MODIFY COLUMN category ENUM('daily','lab','radiology') NOT NULL");
    }
};
