<?php

namespace App\Services;

use App\Enums\UserActivityType;
use App\Models\User;
use App\Models\UserActivity;
use App\Support\DatatableSort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserActivityLogger
{
    public function log(
        UserActivityType $type,
        ?User $user,
        string $description,
        ?array $properties = null,
        ?Request $request = null,
    ): UserActivity {
        $request ??= request();

        return UserActivity::query()->create([
            'user_id' => $user?->id,
            'type' => $type->value,
            'title' => $type->value,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = UserActivity::query()->with('user:id,first_name,last_name,username,email');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('username', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        DatatableSort::apply($query, $filters, [
            'id', 'type', 'created_at', 'user_id',
        ], 'id', 'desc');

        return $query->paginate($perPage);
    }
}
