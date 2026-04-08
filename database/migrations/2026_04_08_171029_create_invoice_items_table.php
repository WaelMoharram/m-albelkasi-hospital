<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->morphs('itemable');          // itemable_id, itemable_type
            $table->unsignedSmallInteger('qty')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 12, 2);
            $table->enum('section', ['local_med', 'imported_med', 'lab', 'radiology', 'daily']);
            $table->date('service_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
