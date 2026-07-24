<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Age and expected duration are computed (from the patient's DOB and from
     * admission_date/discharge_date) rather than entered manually — see
     * InvoiceService::admissionIndicators().
     */
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn(['age', 'expected_duration_days']);
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable()->after('diagnosis');
            $table->unsignedSmallInteger('expected_duration_days')->nullable()->after('patient_type');
        });
    }
};
