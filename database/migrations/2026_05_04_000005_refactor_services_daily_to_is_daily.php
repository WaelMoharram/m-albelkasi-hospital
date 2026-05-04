<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_daily')->default(false)->after('category');
        });

        DB::statement("ALTER TABLE services MODIFY COLUMN category ENUM('supplies','lab','radiology') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN category ENUM('daily','supplies','lab','radiology') NOT NULL");

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('is_daily');
        });
    }
};
