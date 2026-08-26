<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $service) {}

    public function index(): View
    {
        $roles = $this->service->all();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $groupedPermissions = Permission::grouped();
        $groupLabels = Permission::groupLabels();

        return view('roles.create', compact('groupedPermissions', 'groupLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name', 'regex:/^[a-z0-9_]+$/'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_column(Permission::cases(), 'value'))],
        ]);

        $this->service->create($data);

        alert()->success(__('Created'), __('Role created successfully.'));

        return redirect()->route('roles.index');
    }

    public function edit(Role $role): View
    {
        $groupedPermissions = Permission::grouped();
        $groupLabels = Permission::groupLabels();
        $rolePermissions = $role->permissions->pluck('name')->all();

        return view('roles.edit', compact('role', 'groupedPermissions', 'groupLabels', 'rolePermissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id, 'regex:/^[a-z0-9_]+$/'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_column(Permission::cases(), 'value'))],
        ]);

        try {
            $this->service->update($role, $data);
            alert()->success(__('Updated'), __('Role updated successfully.'));
        } catch (LogicException $e) {
            alert()->error(__('Not Allowed'), $e->getMessage());
        }

        return redirect()->route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        try {
            $this->service->delete($role);
            alert()->success(__('Deleted'), __('Role deleted successfully.'));
        } catch (LogicException $e) {
            alert()->error(__('Not Allowed'), $e->getMessage());
        }

        return redirect()->route('roles.index');
    }
}
