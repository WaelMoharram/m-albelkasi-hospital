<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE medications MODIFY COLUMN daily_qty TINYINT UNSIGNED NOT NULL DEFAULT 0");
        DB::table('medications')->update(['daily_qty' => 0]);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE medications MODIFY COLUMN daily_qty TINYINT UNSIGNED NOT NULL DEFAULT 1");
        DB::table('medications')->update(['daily_qty' => 1]);
    }
};
