<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use LogicException;

class UserService
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')
            ->search($search)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],   // already validated, hashed by cast
            'is_active' => true,
        ]);

        $user->syncRoles([$data['role']]);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);

        return $user;
    }

    /**
     * Toggle active/inactive — guards against self-deactivation and
     * deactivating the very last active super_admin.
     */
    public function toggleActive(User $user, User $actor): User
    {
        if ($user->id === $actor->id) {
            throw new LogicException('You cannot deactivate your own account.');
        }

        // Prevent removing the last active super_admin
        if (
            $user->is_active &&
            $user->hasRole(Role::SuperAdmin->value) &&
            User::role(Role::SuperAdmin->value)->where('is_active', true)->count() <= 1
        ) {
            throw new LogicException('Cannot deactivate the last active Super Admin.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return $user;
    }

    /**
     * Hard-delete a user — same guards as toggleActive.
     */
    public function delete(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw new LogicException('You cannot delete your own account.');
        }

        if (
            $user->hasRole(Role::SuperAdmin->value) &&
            User::role(Role::SuperAdmin->value)->count() <= 1
        ) {
            throw new LogicException('Cannot delete the last Super Admin.');
        }

        $user->delete();
    }
}
