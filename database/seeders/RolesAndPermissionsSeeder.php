<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        foreach (Permission::cases() as $permission) {
            PermissionModel::firstOrCreate(['name' => $permission->value]);
        }

        // super_admin — all permissions
        $superAdmin = RoleModel::firstOrCreate(['name' => Role::SuperAdmin->value]);
        $superAdmin->syncPermissions(Permission::cases());

        // admin — catalog, patients, admissions, reports, invoices (no delete)
        $admin = RoleModel::firstOrCreate(['name' => Role::Admin->value]);
        $admin->syncPermissions([
            Permission::ManageCatalog,
            Permission::RegisterPatients,
            Permission::ViewPatients,
            Permission::ManageAdmissions,
            Permission::ViewAdmissions,
            Permission::ViewInvoices,
            Permission::CreateInvoices,
            Permission::EditInvoices,
            Permission::PrintInvoices,
            Permission::ViewReports,
        ]);

        // cashier — view/print invoices, confirm payment
        $cashier = RoleModel::firstOrCreate(['name' => Role::Cashier->value]);
        $cashier->syncPermissions([
            Permission::ViewInvoices,
            Permission::PrintInvoices,
            Permission::ConfirmPayment,
            Permission::ViewAdmissions,
        ]);

        // data_entry — register & view patients, view admissions, add invoice items
        $dataEntry = RoleModel::firstOrCreate(['name' => Role::DataEntry->value]);
        $dataEntry->syncPermissions([
            Permission::RegisterPatients,
            Permission::ViewPatients,
            Permission::ViewAdmissions,
            Permission::AddInvoiceItems,
            Permission::ViewInvoices,
        ]);
    }
}
