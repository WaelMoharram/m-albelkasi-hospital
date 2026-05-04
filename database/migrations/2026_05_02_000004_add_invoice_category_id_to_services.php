<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('invoice_category_id')
                  ->nullable()
                  ->after('category')
                  ->constrained('invoice_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['invoice_category_id']);
            $table->dropColumn('invoice_category_id');
        });
    }
};
