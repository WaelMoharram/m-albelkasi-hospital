<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::table('medications', function (Blueprint $table) {
            $table->string('unit', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
        Schema::table('medications', function (Blueprint $table) {
            $table->string('unit', 100)->nullable(false)->change();
        });
    }
};
