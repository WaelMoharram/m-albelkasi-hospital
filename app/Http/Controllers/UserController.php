<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use LogicException;

class UserController extends Controller
{
    public function __construct(private readonly UserService $service) {}

    public function index(Request $request): View
    {
        $users = $this->service->paginate($request->input('search'));

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        $groupedPermissions = Permission::grouped();
        $groupLabels = Permission::groupLabels();

        return view('users.create', compact('roles', 'groupedPermissions', 'groupLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Password::min(8)],
            'role'          => ['required', 'exists:roles,name'],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_column(Permission::cases(), 'value'))],
        ]);

        $this->service->create($data);

        alert()->success(__('Created'), __('User created successfully.'));

        return redirect()->route('users.index');
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        $groupedPermissions = Permission::grouped();
        $groupLabels = Permission::groupLabels();
        $userDirectPermissions = $user->getDirectPermissions()->pluck('name')->all();

        return view('users.edit', compact('user', 'roles', 'groupedPermissions', 'groupLabels', 'userDirectPermissions'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', "unique:users,email,{$user->id}"],
            'password'      => ['nullable', 'confirmed', Password::min(8)],
            'role'          => ['required', 'exists:roles,name'],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_column(Permission::cases(), 'value'))],
        ]);

        $this->service->update($user, $data);

        alert()->success(__('Updated'), __('User updated successfully.'));

        return redirect()->route('users.index');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        try {
            $this->service->toggleActive($user, auth()->user());
            $isNowActive = $user->fresh()->is_active;
            alert()->success(
                $isNowActive ? __('Activated') : __('Deactivated'),
                $isNowActive ? __('User has been activated.') : __('User has been deactivated.')
            );
        } catch (LogicException $e) {
            alert()->error(__('Not Allowed'), $e->getMessage());
        }

        return redirect()->route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->service->delete($user, auth()->user());
            alert()->success(__('Deleted'), __('User has been deleted.'));
        } catch (LogicException $e) {
            alert()->error(__('Not Allowed'), $e->getMessage());
        }

        return redirect()->route('users.index');
    }
}
