<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

class RoleService
{
    public function all(): Collection
    {
        return Role::withCount(['permissions', 'users'])->orderBy('name')->get();
    }

    public function create(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    public function update(Role $role, array $data): Role
    {
        if ($role->is_protected) {
            throw new LogicException('This role is protected and cannot be modified.');
        }

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    public function delete(Role $role): void
    {
        if ($role->is_protected) {
            throw new LogicException('This role is protected and cannot be deleted.');
        }

        if ($role->users()->exists()) {
            throw new LogicException('Cannot delete a role that is still assigned to users.');
        }

        $role->delete();
    }
}
