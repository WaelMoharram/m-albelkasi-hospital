<?php

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * SettingsSeeder already sets these defaults, but seeders only run on
     * `db:seed`, not on a normal `migrate` deploy — so an existing production
     * database never picked them up. Set them here (only if unset, so an
     * admin's own value on some environment isn't overwritten) and recalc
     * draft invoices so the discount is reflected immediately.
     */
    public function up(): void
    {
        if (Setting::where('key', 'local_med_discount')->doesntExist()) {
            Setting::setValue('local_med_discount', '15');
        }

        if (Setting::where('key', 'imported_med_discount')->doesntExist()) {
            Setting::setValue('imported_med_discount', '7');
        }

        Invoice::where('status', 'draft')->each(fn (Invoice $invoice) => $invoice->recalculateTotal());
    }

    public function down(): void
    {
        // Not safely reversible.
    }
};
