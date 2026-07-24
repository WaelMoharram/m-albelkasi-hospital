<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->string('diagnosis')->nullable()->after('referral_source');
            $table->unsignedTinyInteger('age')->nullable()->after('diagnosis');
            $table->enum('patient_type', ['pension', 'student', 'employee'])->nullable()->after('age');
            $table->unsignedSmallInteger('expected_duration_days')->nullable()->after('patient_type');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn(['diagnosis', 'age', 'patient_type', 'expected_duration_days']);
        });
    }
};
