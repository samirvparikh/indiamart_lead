<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserActivityType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserActivityController extends Controller
{
    public function __construct(
        protected UserActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        return view('user-activities.index', [
            'activityTypes' => UserActivityType::cases(),
            'users' => User::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'username']),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $activities = $this->activityLogger->list(
            $request->only(['search', 'type', 'user_id', 'date_from', 'date_to', 'sort_by', 'sort_dir']),
            (int) $request->input('per_page', 25)
        );

        $data = collect($activities->items())->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->type?->value,
                'description' => $activity->description,
                'ip_address' => $activity->ip_address,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'username' => $activity->user->username,
                    'email' => $activity->user->email,
                ] : null,
                'created_at' => $activity->created_at?->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }
}
