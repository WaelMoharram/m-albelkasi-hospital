<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'delete_admissions', 'guard_name' => 'web']);

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin && ! $superAdmin->hasPermissionTo('delete_admissions')) {
            $superAdmin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'delete_admissions')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
