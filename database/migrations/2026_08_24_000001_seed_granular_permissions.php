<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Grants below replicate exactly what each role can already reach today
     * through route-level role checks and the (previously) unrestricted
     * invoice show page — so introducing these finer permissions changes
     * nothing until roles are deliberately tightened from the new Roles
     * management screen.
     */
    private const INVOICE_FIELD_PERMISSIONS = [
        'view_invoice_patient_id_number',
        'view_invoice_diagnosis',
        'view_invoice_referral_number',
        'view_invoice_insurance',
        'view_invoice_room_ward',
        'view_invoice_age',
        'view_invoice_duration',
        'view_invoice_remaining_days',
        'view_invoice_cost_per_day',
        'view_invoice_patient_type',
        'view_invoice_section_daily',
        'view_invoice_section_local_med',
        'view_invoice_section_imported_med',
        'view_invoice_section_supplies',
        'view_invoice_section_lab',
        'view_invoice_section_other',
        'view_invoice_discounts',
        'view_invoice_grand_total',
        'view_invoice_hio_export',
    ];

    private const CATALOG_PERMISSIONS = [
        'view_medications', 'manage_medications', 'export_medications',
        'view_services', 'manage_services', 'export_services',
        'view_insurance_companies', 'manage_insurance_companies',
        'view_invoice_categories', 'manage_invoice_categories',
        'view_units', 'manage_units',
        'view_wards', 'manage_wards',
        'view_catalog_health',
    ];

    private const REPORT_PERMISSIONS = [
        'view_report_claim',
        'view_report_patient_list',
        'view_report_summary',
        'view_report_performance',
        'export_monthly_report',
    ];

    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allNew = [
            'manage_roles',
            'edit_patients',
            'delete_patients',
            'finalize_invoices',
            ...self::INVOICE_FIELD_PERMISSIONS,
            ...self::CATALOG_PERMISSIONS,
            ...self::REPORT_PERMISSIONS,
            'view_settings',
            'manage_settings',
        ];

        foreach ($allNew as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        $superAdmin?->givePermissionTo($allNew);

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo([
            'edit_patients',
            'delete_patients',
            'finalize_invoices',
            ...self::INVOICE_FIELD_PERMISSIONS,
            ...self::CATALOG_PERMISSIONS,
            ...self::REPORT_PERMISSIONS,
        ]);

        $cashier = Role::where('name', 'cashier')->first();
        $cashier?->givePermissionTo(self::INVOICE_FIELD_PERMISSIONS);

        $dataEntry = Role::where('name', 'data_entry')->first();
        $dataEntry?->givePermissionTo([
            'edit_patients',
            'delete_patients',
            ...self::INVOICE_FIELD_PERMISSIONS,
        ]);
    }

    public function down(): void
    {
        $allNew = [
            'manage_roles',
            'edit_patients',
            'delete_patients',
            'finalize_invoices',
            ...self::INVOICE_FIELD_PERMISSIONS,
            ...self::CATALOG_PERMISSIONS,
            ...self::REPORT_PERMISSIONS,
            'view_settings',
            'manage_settings',
        ];

        Permission::whereIn('name', $allNew)->delete();
    }
};
