<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\User;
use App\Support\DatatableSort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = User::query()
            ->with('roles');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        DatatableSort::apply($query, $filters, [
            'id', 'username', 'first_name', 'last_name', 'email', 'mobile', 'is_active', 'last_login_at', 'created_at',
        ], 'id', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = $data['role'] ?? null;
            unset($data['role'], $data['password_confirmation']);

            $data['email_verified_at'] = $data['email_verified_at'] ?? now();
            $data['username'] = strtolower($data['username']);
            $data['email'] = strtolower($data['email']);
            $data['is_active'] = array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : true;

            $user = User::query()->create($data);

            if ($role) {
                $user->syncRoles([$role]);
            }

            return $user->fresh('roles');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $role = $data['role'] ?? null;
            unset($data['role'], $data['password_confirmation']);

            if (array_key_exists('password', $data) && empty($data['password'])) {
                unset($data['password']);
            }

            if (array_key_exists('is_active', $data)) {
                $data['is_active'] = (bool) $data['is_active'];
            }

            if (isset($data['username'])) {
                $data['username'] = strtolower($data['username']);
            }

            if (isset($data['email'])) {
                $data['email'] = strtolower($data['email']);
            }

            $user->update($data);

            if ($role !== null) {
                if (
                    $user->isSuperAdmin()
                    && $role !== RoleName::SuperAdmin->value
                    && User::role(RoleName::SuperAdmin->value)->count() <= 1
                ) {
                    throw new InvalidArgumentException('Cannot remove the last Super Admin role.');
                }

                $user->syncRoles([$role]);
            }

            return $user->fresh('roles');
        });
    }

    public function delete(User $user): bool
    {
        if ($user->isSuperAdmin() && User::role(RoleName::SuperAdmin->value)->count() <= 1) {
            throw new InvalidArgumentException('Cannot delete the last Super Admin.');
        }

        return (bool) $user->delete();
    }
}
