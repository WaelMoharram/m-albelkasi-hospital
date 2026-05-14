<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->unsignedTinyInteger('daily_qty')->default(1)->after('type');
        });

        Schema::create('medication_service_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')
                  ->constrained('medications')
                  ->cascadeOnDelete();
            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['medication_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_service_triggers');

        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn('daily_qty');
        });
    }
};
