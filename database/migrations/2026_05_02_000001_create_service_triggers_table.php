<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();
            $table->foreignId('triggered_service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_id', 'triggered_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_triggers');
    }
};
